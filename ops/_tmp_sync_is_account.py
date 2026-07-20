#!/usr/bin/env python3
import base64
import json
import subprocess
import sys

REMOTE = "Carmine@192.168.1.50"
DOCKER = "/Volume1/@apps/DockerEngine/dockerd/bin/docker"

GESTIIO = r"""<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo 'COLS='.json_encode(Schema::getColumnListing('users'))."\n";

$admins = DB::table('model_has_roles')
  ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
  ->join('users', 'users.id', '=', 'model_has_roles.model_id')
  ->where('roles.name', 'admin')
  ->orderBy('users.id')
  ->select('users.*')
  ->limit(10)
  ->get();

// strip secrets
$out = [];
foreach ($admins as $u) {
  $row = (array) $u;
  unset($row['password'], $row['remember_token'], $row['two_factor_secret'], $row['two_factor_recovery_codes']);
  $out[] = $row;
}
echo 'ADMINS='.json_encode($out, JSON_UNESCAPED_UNICODE)."\n";
"""

IS_APPLY = r"""<?php
$appRoot = '/var/www/html';
require $appRoot.'/vendor/autoload.php';
$app = require $appRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

$payload = json_decode(file_get_contents('php://stdin'), true);
if (!$payload) { fwrite(STDERR, "no payload\n"); exit(1); }

$name = trim((string)($payload['name'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));
$companyName = trim((string)($payload['company_name'] ?? 'Agenzia Plinio'));
$vat = preg_replace('/\D+/', '', (string)($payload['vat_id'] ?? ''));
$tax = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($payload['tax_id'] ?? '')));
$address = $payload['address'] ?? null;

if ($name === '' || $email === '') {
  fwrite(STDERR, "name/email required\n");
  exit(1);
}

DB::beginTransaction();
try {
  $user = DB::table('users')->orderBy('id')->first();
  if (!$user) { throw new RuntimeException('no IS user'); }

  $upd = [
    'name' => $name,
    'email' => $email,
    'updated_at' => now(),
  ];
  if (Schema::hasColumn('users', 'phone')) {
    $upd['phone'] = $phone !== '' ? $phone : null;
  }
  DB::table('users')->where('id', $user->id)->update($upd);
  echo "user updated id={$user->id}\n";

  $company = DB::table('companies')->orderBy('id')->first();
  if ($company) {
    $cUpd = [
      'name' => $companyName,
      'updated_at' => now(),
    ];
    if (Schema::hasColumn('companies', 'vat_id') && $vat !== '') {
      $cUpd['vat_id'] = $vat;
    }
    if (Schema::hasColumn('companies', 'tax_id') && $tax !== '') {
      $cUpd['tax_id'] = $tax;
    }
    DB::table('companies')->where('id', $company->id)->update($cUpd);
    echo "company updated id={$company->id}\n";

    if (is_array($address) && Schema::hasTable('addresses')) {
      $street = trim((string)($address['street'] ?? ''));
      $city = trim((string)($address['city'] ?? ''));
      $zip = trim((string)($address['zip'] ?? ''));
      $state = trim((string)($address['state'] ?? ''));
      $existing = DB::table('addresses')->where('company_id', $company->id)->where('type', 'company')->first();
      if (!$existing) {
        $existing = DB::table('addresses')->where('company_id', $company->id)->first();
      }
      $aData = [
        'name' => $companyName,
        'address_street_1' => $street,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'phone' => $phone !== '' ? $phone : null,
        'type' => 'company',
        'company_id' => $company->id,
        'updated_at' => now(),
      ];
      // country_id for Italy if present
      if (Schema::hasTable('countries')) {
        $it = DB::table('countries')->where('code', 'IT')->orWhere('name', 'like', 'Italy%')->orWhere('name', 'like', 'Italia%')->first();
        if ($it) { $aData['country_id'] = $it->id; }
      }
      if ($existing) {
        DB::table('addresses')->where('id', $existing->id)->update($aData);
        echo "address updated id={$existing->id}\n";
      } else {
        $aData['created_at'] = now();
        $id = DB::table('addresses')->insertGetId($aData);
        echo "address created id=$id\n";
      }
    }
  }

  DB::commit();
  $u = DB::table('users')->where('id', $user->id)->first(['id','name','email','phone']);
  echo 'RESULT_USER='.json_encode($u, JSON_UNESCAPED_UNICODE)."\n";
  echo 'RESULT_COMPANY='.json_encode(DB::table('companies')->first(), JSON_UNESCAPED_UNICODE)."\n";
} catch (Throwable $e) {
  DB::rollBack();
  fwrite(STDERR, $e->getMessage()."\n");
  exit(1);
}
"""


def run_php(container: str, php: str, stdin_json: dict | None = None) -> tuple[int, str, str]:
    b64 = base64.b64encode(php.encode("utf-8")).decode("ascii")
    if stdin_json is None:
        inner = "base64 -d > /tmp/sync_is.php && php /tmp/sync_is.php"
        sh = (
            f"set -e\n"
            f"{DOCKER} exec -i {container} sh -c {json.dumps(inner)} <<EOF\n"
            f"{b64}\n"
            f"EOF\n"
        )
        p = subprocess.run(["ssh", REMOTE, "bash", "-s"], input=sh.encode(), capture_output=True)
    else:
        # write php then pipe json
        payload = json.dumps(stdin_json, ensure_ascii=False)
        pb64 = base64.b64encode(payload.encode("utf-8")).decode("ascii")
        inner = (
            "base64 -d > /tmp/sync_is.php && "
            "base64 -d > /tmp/sync_payload.json && "
            "php /tmp/sync_is.php < /tmp/sync_payload.json"
        )
        # two base64 blobs concatenated with a marker is messy; use one script on host
        sh = (
            "set -e\n"
            f"echo {b64} | base64 -d > /tmp/host_sync_is.php\n"
            f"echo {pb64} | base64 -d > /tmp/host_sync_payload.json\n"
            f"{DOCKER} cp /tmp/host_sync_is.php {container}:/tmp/sync_is.php\n"
            f"{DOCKER} cp /tmp/host_sync_payload.json {container}:/tmp/sync_payload.json\n"
            f"{DOCKER} exec {container} sh -c 'php /tmp/sync_is.php < /tmp/sync_payload.json'\n"
        )
        p = subprocess.run(["ssh", REMOTE, "bash", "-s"], input=sh.encode(), capture_output=True)
    return p.returncode, p.stdout.decode("utf-8", "replace"), p.stderr.decode("utf-8", "replace")


if __name__ == "__main__":
    mode = sys.argv[1] if len(sys.argv) > 1 else "read-gestiio"
    if mode == "read-gestiio":
        rc, out, err = run_php("gestiio-app", GESTIIO)
        print(out)
        print(err[-1500:] if err else "")
        sys.exit(rc)
    elif mode == "apply":
        # payload from argv json file or hardcoded after read
        path = sys.argv[2]
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        rc, out, err = run_php("invoiceshelf", IS_APPLY, data)
        print(out)
        print(err[-2000:] if err else "")
        sys.exit(rc)
    else:
        sys.exit("usage: read-gestiio | apply payload.json")
