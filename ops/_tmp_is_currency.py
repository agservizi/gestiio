#!/usr/bin/env python3
import base64
import subprocess
import sys

REMOTE = "Carmine@192.168.1.50"

PHP = """<?php
$appRoot = is_file('/var/www/html/artisan') ? '/var/www/html' : '/app';
require $appRoot.'/vendor/autoload.php';
$app = require $appRoot.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

$doFix = ($argv[1] ?? '') === 'fix';

$eur = DB::table('currencies')->where('code', 'EUR')->first();
if (!$eur) { fwrite(STDERR, "EUR not found\\n"); exit(1); }
echo "EUR id={$eur->id} name={$eur->name}\\n";

foreach (DB::table('companies')->get() as $c) {
  $cid = isset($c->currency_id) ? $c->currency_id : 'n/a';
  echo "company id={$c->id} name={$c->name} currency_id={$cid}\\n";
}

if (Schema::hasTable('company_settings')) {
  foreach (DB::table('company_settings')->whereIn('option', ['currency','currency_id'])->get() as $s) {
    echo "setting company={$s->company_id} option={$s->option} value={$s->value}\\n";
  }
}

foreach (['invoices','estimates','payments','expenses','customers','items'] as $t) {
  if (Schema::hasTable($t)) {
    echo "$t count=".DB::table($t)->count()."\\n";
  }
}

if (!$doFix) { echo "DRY_RUN\\n"; exit(0); }

DB::beginTransaction();
try {
  if (Schema::hasColumn('companies', 'currency_id')) {
    echo 'companies updated='.DB::table('companies')->update(['currency_id' => $eur->id])."\\n";
  }
  if (Schema::hasTable('company_settings')) {
    $u = DB::table('company_settings')->whereIn('option', ['currency','currency_id'])->update(['value' => (string)$eur->id]);
    echo "company_settings updated=$u\\n";
    if ($u === 0) {
      foreach (DB::table('companies')->pluck('id') as $companyId) {
        DB::table('company_settings')->updateOrInsert(
          ['company_id' => $companyId, 'option' => 'currency'],
          ['value' => (string)$eur->id]
        );
      }
      echo "company_settings inserted\\n";
    }
  }
  foreach (['customers','invoices','estimates','payments','expenses','recurring_invoices','items'] as $t) {
    if (Schema::hasTable($t) && Schema::hasColumn($t, 'currency_id')) {
      echo "$t -> EUR rows=".DB::table($t)->update(['currency_id' => $eur->id])."\\n";
    }
  }
  DB::commit();
  echo "FIXED\\n";
} catch (Throwable $e) {
  DB::rollBack();
  fwrite(STDERR, $e->getMessage()."\\n");
  exit(1);
}
"""

mode = sys.argv[1] if len(sys.argv) > 1 else "inspect"
if mode not in ("inspect", "fix"):
    sys.exit("usage: inspect|fix")

# Shell script with LF only, PHP embedded via base64 to avoid heredoc issues
php_b64 = base64.b64encode(PHP.encode("utf-8")).decode("ascii")
sh = (
    "set -e\n"
    "D=/Volume1/@apps/DockerEngine/dockerd/bin/docker\n"
    f"echo {php_b64} | base64 -d > /tmp/is_fix_currency.php\n"
    f"$D cp /tmp/is_fix_currency.php invoiceshelf:/tmp/fix_currency.php\n"
    f"$D exec invoiceshelf php /tmp/fix_currency.php {mode}\n"
)
# Also need to put file on NAS first - docker cp from host
# Better: pipe base64 into docker exec
sh = (
    "set -e\n"
    "D=/Volume1/@apps/DockerEngine/dockerd/bin/docker\n"
    f"$D exec -i invoiceshelf sh -c 'base64 -d > /tmp/fix_currency.php && php /tmp/fix_currency.php {mode}' <<EOF\n"
    f"{php_b64}\n"
    "EOF\n"
)

p = subprocess.run(
    ["ssh", REMOTE, "bash", "-s"],
    input=sh.encode("utf-8"),  # bytes, LF only
    capture_output=True,
)
sys.stdout.buffer.write(p.stdout)
sys.stderr.buffer.write(p.stderr)
sys.exit(p.returncode)
