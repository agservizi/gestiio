#!/usr/bin/env python3
import base64
import json
import subprocess
import sys
from pathlib import Path

REMOTE = "Carmine@192.168.1.50"
DOCKER = "/Volume1/@apps/DockerEngine/dockerd/bin/docker"

PHP = r"""<?php
$appRoot = '/var/www/html';
require $appRoot.'/vendor/autoload.php';
$app = require $appRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$payload = json_decode(file_get_contents('/tmp/sync_payload.json'), true);
if (!$payload) { fwrite(STDERR, "no payload\n"); exit(1); }

$name = trim((string)($payload['name'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));
$companyName = trim((string)($payload['company_name'] ?? ''));
$vat = preg_replace('/\D+/', '', (string)($payload['vat_id'] ?? ''));
$tax = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($payload['tax_id'] ?? '')));
$address = is_array($payload['address'] ?? null) ? $payload['address'] : [];

if ($name === '' || $email === '') {
  fwrite(STDERR, "name/email required\n");
  exit(1);
}

DB::beginTransaction();
try {
  $user = DB::table('users')->orderBy('id')->first();
  if (!$user) { throw new RuntimeException('no IS user'); }

  $upd = ['name' => $name, 'email' => $email, 'updated_at' => now()];
  if (Schema::hasColumn('users', 'phone')) {
    $upd['phone'] = $phone !== '' ? $phone : null;
  }
  DB::table('users')->where('id', $user->id)->update($upd);
  echo "user updated id={$user->id}\n";

  $company = DB::table('companies')->orderBy('id')->first();
  if ($company) {
    $cUpd = ['name' => $companyName !== '' ? $companyName : $company->name, 'updated_at' => now()];
    if (Schema::hasColumn('companies', 'vat_id') && $vat !== '') {
      $cUpd['vat_id'] = $vat;
    }
    if (Schema::hasColumn('companies', 'tax_id') && $tax !== '') {
      $cUpd['tax_id'] = $tax;
    }
    DB::table('companies')->where('id', $company->id)->update($cUpd);
    echo "company updated id={$company->id}\n";

    if ($address && Schema::hasTable('addresses')) {
      $street = trim((string)($address['street'] ?? ''));
      $city = trim((string)($address['city'] ?? ''));
      $zip = trim((string)($address['zip'] ?? ''));
      $state = trim((string)($address['state'] ?? ''));
      $existing = DB::table('addresses')->where('company_id', $company->id)->first();
      $aData = [
        'name' => $companyName !== '' ? $companyName : $name,
        'address_street_1' => $street,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'phone' => $phone !== '' ? $phone : null,
        'type' => 'company',
        'company_id' => $company->id,
        'updated_at' => now(),
      ];
      if (Schema::hasTable('countries')) {
        $it = DB::table('countries')->where('code', 'IT')->first();
        if (!$it) {
          $it = DB::table('countries')->where('name', 'like', 'Ital%')->first();
        }
        if ($it) {
          $aData['country_id'] = $it->id;
        }
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

  // company settings: company_name if present
  if (Schema::hasTable('company_settings')) {
    foreach ([
      'company_name' => $companyName,
      'company_address' => trim((string)($address['street'] ?? '')),
      'company_city' => trim((string)($address['city'] ?? '')),
      'company_state' => trim((string)($address['state'] ?? '')),
      'company_zip_code' => trim((string)($address['zip'] ?? '')),
      'company_phone' => $phone,
    ] as $opt => $val) {
      if ($val === '') continue;
      $exists = DB::table('company_settings')->where('company_id', $company->id)->where('option', $opt)->first();
      if ($exists) {
        DB::table('company_settings')->where('id', $exists->id)->update(['value' => $val]);
      } else {
        DB::table('company_settings')->insert([
          'company_id' => $company->id,
          'option' => $opt,
          'value' => $val,
        ]);
      }
    }
    echo "company_settings synced\n";
  }

  DB::commit();
  echo 'RESULT_USER='.json_encode(DB::table('users')->where('id', $user->id)->first(['id','name','email','phone']), JSON_UNESCAPED_UNICODE)."\n";
  echo 'RESULT_COMPANY='.json_encode(DB::table('companies')->first(), JSON_UNESCAPED_UNICODE)."\n";
  if (Schema::hasTable('addresses')) {
    echo 'RESULT_ADDR='.json_encode(DB::table('addresses')->where('company_id', $company->id)->get(), JSON_UNESCAPED_UNICODE)."\n";
  }
} catch (Throwable $e) {
  DB::rollBack();
  fwrite(STDERR, $e->getMessage()."\n");
  exit(1);
}
"""

payload_path = Path(sys.argv[1] if len(sys.argv) > 1 else "ops/_tmp_is_account_payload.json")
payload = json.loads(payload_path.read_text(encoding="utf-8"))
php_b64 = base64.b64encode(PHP.encode()).decode()
json_b64 = base64.b64encode(json.dumps(payload, ensure_ascii=False).encode()).decode()

sh = (
    "set -e\n"
    f"echo {php_b64} | base64 -d > /tmp/host_sync_is.php\n"
    f"echo {json_b64} | base64 -d > /tmp/host_sync_payload.json\n"
    f"{DOCKER} cp /tmp/host_sync_is.php invoiceshelf:/tmp/sync_is.php\n"
    f"{DOCKER} cp /tmp/host_sync_payload.json invoiceshelf:/tmp/sync_payload.json\n"
    f"{DOCKER} exec invoiceshelf php /tmp/sync_is.php\n"
    f"{DOCKER} exec invoiceshelf php /var/www/html/artisan cache:clear\n"
)
p = subprocess.run(["ssh", REMOTE, "bash", "-s"], input=sh.encode(), capture_output=True)
sys.stdout.buffer.write(p.stdout)
sys.stderr.buffer.write(p.stderr)
sys.exit(p.returncode)
