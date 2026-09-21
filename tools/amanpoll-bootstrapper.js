#!/usr/bin/env node
'use strict';

/**
 * Amanpoll Bootstrapper
 * -----------------------------------------------------------------------------
 * Satu file untuk:
 * - Membuat project Laravel 13 (jika belum ada)
 * - Memasang Inertia + React + Tailwind CSS 4 + fondasi shadcn/ui
 * - Menyiapkan queue database + cron-friendly worker untuk shared hosting
 * - Membuat struktur Modular Monolith / DDD Amanpoll
 * - Membaca Amanpoll_Database_MySQL.sql dan menghasilkan seluruh Eloquent Model
 *   berdasarkan tabel, kolom, cast, soft delete, tenancy, dan foreign key
 * - Menghasilkan tipe TypeScript dari schema SQL
 * - Membuat auth dasar menggunakan tabel Pengguna
 * - Membuat middleware Organisasi dan Izin
 * - Membuat layout/dashboard Inertia dasar
 * - Membuat struktur backend/frontend, pengujian, dokumentasi, dan paket deployment Niagahoster
 *
 * Minimum:
 *   Node.js 20+
 *   PHP 8.3+ (8.4 direkomendasikan)
 *   Composer 2+
 *   MySQL 8+
 *
 * Contoh:
 *   node index.js --dir=Amanpoll --sql=./Amanpoll_Database_MySQL.sql
 *   node index.js --dir=Amanpoll --sql=./Amanpoll_Database_MySQL.sql --import-sql
 *   node index.js --dir=. --sql=./Amanpoll_Database_MySQL.sql --force
 *
 * Opsi:
 *   --dir=<folder>          Folder project. Default: ./Amanpoll jika bukan project Laravel.
 *   --sql=<file>           Path SQL Amanpoll. Auto-detect jika tidak diberikan.
 *   --force                Timpa file scaffold yang sudah ada.
 *   --skip-install         Lewati composer/npm install.
 *   --skip-shadcn          Lewati `shadcn add` (fondasi tetap dibuat).
 *   --import-sql           Import SQL melalui mysql CLI setelah setup.
 *   --db-host=127.0.0.1
 *   --db-port=3306
 *   --db-name=Amanpoll
 *   --db-user=root
 *   --db-password=...
 *   --help
 */

const fs = require('fs');
const path = require('path');
const os = require('os');
const { spawnSync } = require('child_process');

const APP_NAME = 'Amanpoll';
const REQUIRED_NODE_MAJOR = 20;
const REQUIRED_PHP = [8, 3];
const RECOMMENDED_PHP = [8, 4];
const HOSTING_PROFILE = 'niagahoster-business';

const SECTION_DOMAIN = {
  '01': 'Platform',
  '02': 'Kolaborasi',
  '03': 'Penyedia',
  '04': 'Aset',
  '05': 'SiklusAset',
  '06': 'Pemeliharaan',
  '07': 'PreventifInspeksi',
  '08': 'Kalibrasi',
  '09': 'Persediaan',
  '10': 'PerencanaanPengadaan',
  '11': 'Kontrak',
  '12': 'Kepatuhan',
  '13': 'Persetujuan',
  '14': 'Notifikasi',
  '15': 'IntegrasiAudit',
  '16': 'Sinkronisasi',
  '17': 'Pelaporan',
  '18': 'Langganan',
};

const FRONTEND_FEATURES = [
  'Dashboard', 'Organisasi', 'UnitOrganisasi', 'Lokasi', 'Pengguna', 'PeranIzin',
  'Berkas', 'Tag', 'KolomKustom', 'Penyedia', 'Aset', 'MutasiAset', 'SerahTerimaAset',
  'PenghapusanAset', 'Keluhan', 'PerintahKerja', 'TingkatLayanan', 'Pemeliharaan',
  'DaftarPeriksa', 'Inspeksi', 'Kalibrasi', 'Gudang', 'SukuCadang', 'Stok', 'Anggaran',
  'UsulanAset', 'RencanaPengadaan', 'PermintaanPembelian', 'PermintaanPenawaran',
  'PesananPembelian', 'PenerimaanPembelian', 'TagihanPenyedia', 'Kontrak', 'Kepatuhan',
  'Sertifikasi', 'Persetujuan', 'Notifikasi', 'Integrasi', 'Audit', 'Sinkronisasi',
  'Laporan', 'DashboardKustom', 'PaketLangganan', 'Langganan', 'Pengaturan'
];

const COMPOSER_PACKAGES = [
  'inertiajs/inertia-laravel',
  'league/flysystem-aws-s3-v3',
];

const COMPOSER_DEV_PACKAGES = [
  'larastan/larastan',
];

const NPM_PACKAGES = [
  '@inertiajs/react',
  'react',
  'react-dom',
  'axios',
  'lucide-react',
  'clsx',
  'tailwind-merge',
  'class-variance-authority',
  'sonner',
  'zod',
  'react-hook-form',
  '@hookform/resolvers',
  'date-fns',
  '@radix-ui/react-slot',
];

const NPM_DEV_PACKAGES = [
  '@vitejs/plugin-react',
  'vite',
  'typescript',
  '@types/react',
  '@types/react-dom',
  'tailwindcss',
  '@tailwindcss/vite',
  'eslint',
  'prettier',
];

function printHelp() {
  console.log(`\n${APP_NAME} Bootstrapper\n\n` +
`Pemakaian:\n  node index.js [opsi]\n\n` +
`Opsi:\n` +
`  --dir=<folder>          Folder project\n` +
`  --sql=<file>           File SQL Amanpoll\n` +
`  --force                Timpa file scaffold yang sudah ada\n` +
`  --skip-install         Lewati instalasi composer/npm\n` +
`  --skip-shadcn          Lewati instalasi komponen shadcn\n` +
`  --import-sql           Import SQL via mysql CLI\n` +
`  --db-host=<host>       Default 127.0.0.1\n` +
`  --db-port=<port>       Default 3306\n` +
`  --db-name=<name>       Default Amanpoll\n` +
`  --db-user=<user>       Default root\n` +
`  --db-password=<pass>   Default kosong\n` +
`  --php=<binary>         Binary PHP jika CLI hosting berbeda (contoh /opt/alt/php84/usr/bin/php)\n` +
`  --composer=<binary>    Composer binary (composer/composer2)\n` +
`  --help                 Bantuan\n`);
}

function parseArgs(argv) {
  const out = {};
  for (const arg of argv) {
    if (!arg.startsWith('--')) continue;
    const raw = arg.slice(2);
    const idx = raw.indexOf('=');
    if (idx === -1) out[raw] = true;
    else out[raw.slice(0, idx)] = raw.slice(idx + 1);
  }
  return out;
}

const args = parseArgs(process.argv.slice(2));
if (args.help) {
  printHelp();
  process.exit(0);
}

function log(msg) { console.log(`\x1b[36m[Amanpoll]\x1b[0m ${msg}`); }
function ok(msg) { console.log(`\x1b[32m[OK]\x1b[0m ${msg}`); }
function warn(msg) { console.warn(`\x1b[33m[WARN]\x1b[0m ${msg}`); }
function fail(msg) { console.error(`\x1b[31m[ERROR]\x1b[0m ${msg}`); }

function exists(p) { return fs.existsSync(p); }
function mkdir(p) { fs.mkdirSync(p, { recursive: true }); }

function commandExists(command) {
  const probe = process.platform === 'win32' ? 'where' : 'which';
  return spawnSync(probe, [command], { stdio: 'ignore' }).status === 0;
}

function resolveCommand(command) {
  if (command === 'php') return String(args.php || 'php');
  if (command === 'composer') {
    if (args.composer) return String(args.composer);
    if (commandExists('composer2')) return 'composer2';
    return 'composer';
  }
  return command;
}

function run(command, commandArgs = [], options = {}) {
  const resolvedCommand = resolveCommand(command);
  const label = options.label || `${resolvedCommand} ${commandArgs.join(' ')}`;
  log(label);
  const result = spawnSync(resolvedCommand, commandArgs, {
    cwd: options.cwd || process.cwd(),
    stdio: options.stdio || 'inherit',
    env: { ...process.env, ...(options.env || {}) },
    input: options.input,
    shell: false,
  });
  if (result.error) throw result.error;
  if (result.status !== 0 && !options.allowFailure) {
    throw new Error(`${label} gagal dengan exit code ${result.status}`);
  }
  return result;
}

function capture(command, commandArgs = [], cwd = process.cwd()) {
  const result = spawnSync(resolveCommand(command), commandArgs, {
    cwd,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
    shell: false,
  });
  return {
    status: result.status,
    stdout: (result.stdout || '').trim(),
    stderr: (result.stderr || '').trim(),
  };
}

function versionTuple(s) {
  const m = String(s).match(/(\d+)\.(\d+)(?:\.(\d+))?/);
  return m ? [Number(m[1]), Number(m[2]), Number(m[3] || 0)] : [0, 0, 0];
}

function gteVersion(actual, required) {
  for (let i = 0; i < required.length; i++) {
    if ((actual[i] || 0) > required[i]) return true;
    if ((actual[i] || 0) < required[i]) return false;
  }
  return true;
}

function preflight({ needComposer = true }) {
  if (!commandExists('node')) throw new Error('Node.js tidak ditemukan. Gunakan Node.js 20+.');
  const nodeVersion = versionTuple(process.version);
  if (nodeVersion[0] < REQUIRED_NODE_MAJOR) {
    throw new Error(`Node.js ${REQUIRED_NODE_MAJOR}+ dibutuhkan. Versi saat ini: ${process.version}`);
  }

  const phpCommand = resolveCommand('php');
  if (!commandExists(phpCommand) && !exists(phpCommand)) {
    throw new Error(`PHP tidak ditemukan: ${phpCommand}. Laravel 13 membutuhkan PHP 8.3+.`);
  }
  const php = capture('php', ['-r', 'echo PHP_VERSION;']);
  if (!gteVersion(versionTuple(php.stdout), REQUIRED_PHP)) {
    throw new Error(`PHP 8.3+ dibutuhkan oleh Laravel 13. Versi saat ini: ${php.stdout || 'tidak diketahui'}`);
  }

  if (needComposer) {
    const composerCommand = resolveCommand('composer');
    if (!commandExists(composerCommand) && !exists(composerCommand)) {
      throw new Error('Composer 2 tidak ditemukan. Gunakan --composer=<path> bila perlu.');
    }
    if (!commandExists('npm')) throw new Error('npm tidak ditemukan. npm diperlukan untuk setup/build frontend.');
  }

  if (!gteVersion(versionTuple(php.stdout), RECOMMENDED_PHP)) {
    warn(`PHP ${php.stdout} valid untuk Laravel 13, tetapi PHP 8.4 direkomendasikan untuk Amanpoll.`);
  }
  ok(`Preflight lolos: Node ${process.version}, PHP ${php.stdout}`);
}

function normalizePath(p) {
  return path.resolve(p).replace(/[\\/]+$/, '');
}

const cwd = process.cwd();
const cwdIsLaravel = exists(path.join(cwd, 'artisan')) && exists(path.join(cwd, 'composer.json'));
const requestedDir = args.dir ? normalizePath(args.dir) : null;
const targetDir = requestedDir || (cwdIsLaravel ? cwd : path.join(cwd, 'Amanpoll'));
const force = Boolean(args.force);
const skipInstall = Boolean(args['skip-install']);
const skipShadcn = Boolean(args['skip-shadcn']);
let projectWasCreated = false;

function detectSql() {
  const candidates = [];
  if (args.sql) candidates.push(normalizePath(args.sql));
  candidates.push(
    path.join(cwd, 'Amanpoll_Database_MySQL.sql'),
    path.join(__dirname, 'Amanpoll_Database_MySQL.sql'),
    path.join(path.dirname(targetDir), 'Amanpoll_Database_MySQL.sql'),
    path.join(targetDir, 'Amanpoll_Database_MySQL.sql'),
  );
  return candidates.find((p) => exists(p)) || null;
}

function createLaravelProject() {
  if (exists(path.join(targetDir, 'artisan'))) {
    ok(`Project Laravel ditemukan: ${targetDir}`);
    return;
  }

  if (exists(targetDir) && fs.readdirSync(targetDir).length > 0) {
    throw new Error(`Folder target tidak kosong dan bukan project Laravel: ${targetDir}`);
  }

  mkdir(path.dirname(targetDir));
  run('composer', ['create-project', 'laravel/laravel:^13.0', targetDir, '--no-interaction'], {
    label: `Membuat Laravel 13 di ${targetDir}`,
  });
  projectWasCreated = true;
  ok('Laravel 13 berhasil dibuat.');
}

function backupFile(absPath) {
  if (!exists(absPath)) return;
  const backupRoot = path.join(targetDir, '.amanpoll-backup');
  const rel = path.relative(targetDir, absPath);
  const dest = path.join(backupRoot, rel);
  if (!exists(dest)) {
    mkdir(path.dirname(dest));
    fs.copyFileSync(absPath, dest);
  }
}

function write(rel, content, options = {}) {
  const abs = path.join(targetDir, rel);
  const overwrite = options.overwrite ?? (projectWasCreated || force);
  if (exists(abs) && !overwrite) {
    return false;
  }
  if (exists(abs)) backupFile(abs);
  mkdir(path.dirname(abs));
  fs.writeFileSync(abs, content.replace(/\r\n/g, '\n'), 'utf8');
  return true;
}

function touchKeep(relDir) {
  mkdir(path.join(targetDir, relDir));
  const p = path.join(targetDir, relDir, '.gitkeep');
  if (!exists(p)) fs.writeFileSync(p, '', 'utf8');
}

function sqlTanpaCreateDatabase(sql) {
  return sql
    .replace(/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`[^`]+`[\s\S]*?;\s*/i, '')
    .replace(/^USE\s+`[^`]+`;\s*$/gim, '')
    .trimStart();
}

function copySql(sqlPath) {
  if (!sqlPath) {
    warn('File Amanpoll_Database_MySQL.sql tidak ditemukan. Model otomatis tidak akan dibuat. Gunakan --sql=<path>.');
    return null;
  }

  const original = fs.readFileSync(sqlPath, 'utf8');
  const dest = path.join(targetDir, 'database', 'schema', 'Amanpoll_Database_MySQL.sql');
  const hostingDest = path.join(targetDir, 'database', 'schema', 'Amanpoll_Schema_Hosting.sql');
  mkdir(path.dirname(dest));

  if (!exists(dest) || force || projectWasCreated) fs.writeFileSync(dest, original, 'utf8');
  fs.writeFileSync(hostingDest, sqlTanpaCreateDatabase(original), 'utf8');

  ok('SQL disalin ke database/schema/Amanpoll_Database_MySQL.sql');
  ok('SQL hosting-safe dibuat: database/schema/Amanpoll_Schema_Hosting.sql');
  return dest;
}

function installDependencies() {
  if (skipInstall) {
    warn('Instalasi dependency dilewati (--skip-install).');
    return;
  }

  run('composer', ['require', ...COMPOSER_PACKAGES, '--no-interaction', '--with-all-dependencies'], {
    cwd: targetDir,
    label: 'Memasang package Composer runtime',
  });
  run('composer', ['require', '--dev', ...COMPOSER_DEV_PACKAGES, '--no-interaction', '--with-all-dependencies'], {
    cwd: targetDir,
    label: 'Memasang package Composer development',
  });

  run('npm', ['install', ...NPM_PACKAGES], {
    cwd: targetDir,
    label: 'Memasang package NPM runtime',
  });
  run('npm', ['install', '--save-dev', ...NPM_DEV_PACKAGES], {
    cwd: targetDir,
    label: 'Memasang package NPM development',
  });
}

function patchEnvFile(rel) {
  const abs = path.join(targetDir, rel);
  if (!exists(abs)) return;
  let s = fs.readFileSync(abs, 'utf8');
  const values = {
    APP_NAME: 'Amanpoll',
    APP_ENV: rel === '.env.example' ? 'local' : 'local',
    APP_DEBUG: 'true',
    APP_LOCALE: 'id',
    APP_FALLBACK_LOCALE: 'id',
    APP_FAKER_LOCALE: 'id_ID',
    APP_TIMEZONE: 'Asia/Jakarta',
    DB_CONNECTION: 'mysql',
    DB_HOST: String(args['db-host'] || '127.0.0.1'),
    DB_PORT: String(args['db-port'] || '3306'),
    DB_DATABASE: String(args['db-name'] || 'Amanpoll'),
    DB_USERNAME: String(args['db-user'] || 'root'),
    DB_PASSWORD: String(args['db-password'] || ''),
    CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'database',
    SESSION_DRIVER: 'file',
    BROADCAST_CONNECTION: 'log',
    FILESYSTEM_DISK: 'local',
    LOG_CHANNEL: 'stack',
    LOG_LEVEL: 'debug',
  };

  for (const [key, value] of Object.entries(values)) {
    const escaped = String(value).includes(' ') ? `"${value}"` : String(value);
    const re = new RegExp(`^${key}=.*$`, 'm');
    if (re.test(s)) s = s.replace(re, `${key}=${escaped}`);
    else s += `\n${key}=${escaped}`;
  }

  const block = `\n# Amanpoll - Shared Hosting / Niagahoster Business\n` +
`DB_QUEUE_TABLE=AntrianPekerjaan\n` +
`DB_QUEUE=default\n` +
`DB_FAILED_JOBS_TABLE=PekerjaanGagal\n` +
`DB_JOB_BATCHES_TABLE=KelompokAntrianPekerjaan\n` +
`SESSION_TABLE=SesiAplikasi\n` +
`CACHE_TABLE=CacheAplikasi\n` +
`CACHE_LOCK_TABLE=KunciCacheAplikasi\n` +
`AMANPOLL_HEADER_ORGANISASI=X-Organisasi-Id\n` +
`AMANPOLL_AUDIT_AKTIF=true\n` +
`AMANPOLL_MATA_UANG=IDR\n` +
`AMANPOLL_ZONA_WAKTU=Asia/Jakarta\n` +
`AMANPOLL_QUEUE_CRON=true\n` +
`\n# Object Storage opsional. Default Amanpoll tetap local/private.\n` +
`AWS_ACCESS_KEY_ID=\n` +
`AWS_SECRET_ACCESS_KEY=\n` +
`AWS_DEFAULT_REGION=ap-southeast-1\n` +
`AWS_BUCKET=\n` +
`AWS_USE_PATH_STYLE_ENDPOINT=false\n` +
`AWS_ENDPOINT=\n`;

  if (!s.includes('AMANPOLL_HEADER_ORGANISASI=')) s += block;
  backupFile(abs);
  fs.writeFileSync(abs, s.trimEnd() + '\n', 'utf8');
}

function prepareEnv() {
  const env = path.join(targetDir, '.env');
  const example = path.join(targetDir, '.env.example');
  if (!exists(env) && exists(example)) fs.copyFileSync(example, env);
  patchEnvFile('.env');
  patchEnvFile('.env.example');
  if (!skipInstall && exists(env)) {
    run('php', ['artisan', 'key:generate', '--force'], { cwd: targetDir, label: 'Membuat APP_KEY', allowFailure: true });
  }
}

function parseColumns(block) {
  const columns = [];
  for (const rawLine of block.split(/\r?\n/)) {
    const line = rawLine.trim();
    const m = line.match(/^`([^`]+)`\s+(.+?)(?:,)?$/);
    if (!m) continue;
    const name = m[1];
    const definition = m[2];
    const typeMatch = definition.match(/^([A-Z]+)(?:\(([^)]+)\))?/i);
    if (!typeMatch) continue;
    columns.push({
      name,
      sqlType: typeMatch[1].toUpperCase(),
      length: typeMatch[2] || null,
      nullable: /\bNULL\b/i.test(definition) && !/\bNOT NULL\b/i.test(definition),
      definition,
    });
  }
  return columns;
}

function parseForeignKeys(block) {
  const out = [];
  const re = /FOREIGN KEY\s*\(`([^`]+)`\)\s*REFERENCES\s*`([^`]+)`\s*\(`([^`]+)`\)/gi;
  let m;
  while ((m = re.exec(block)) !== null) {
    out.push({ column: m[1], targetTable: m[2], targetColumn: m[3] });
  }
  return out;
}

function parseSqlSchema(sql) {
  const lines = sql.split(/\r?\n/);
  const tables = [];
  const views = [];
  let currentSection = '00';
  let currentDomain = 'Lainnya';

  for (let i = 0; i < lines.length; i++) {
    const sectionMatch = lines[i].match(/^--\s+(\d{2})\./);
    if (sectionMatch) {
      currentSection = sectionMatch[1];
      currentDomain = SECTION_DOMAIN[currentSection] || 'Lainnya';
    }

    const tableMatch = lines[i].match(/^CREATE TABLE\s+`([^`]+)`\s*\(/i);
    if (tableMatch) {
      const name = tableMatch[1];
      const body = [];
      i++;
      while (i < lines.length && !/\)\s*ENGINE=/i.test(lines[i])) {
        body.push(lines[i]);
        i++;
      }
      const block = body.join('\n');
      const primary = block.match(/PRIMARY KEY\s*\(([^)]+)\)/i);
      const primaryKeys = primary ? [...primary[1].matchAll(/`([^`]+)`/g)].map((m) => m[1]) : [];
      tables.push({
        name,
        section: currentSection,
        domain: currentDomain,
        columns: parseColumns(block),
        foreignKeys: parseForeignKeys(block),
        primaryKeys,
      });
    }

    const viewMatch = lines[i].match(/^CREATE OR REPLACE VIEW\s+`([^`]+)`/i);
    if (viewMatch) views.push({ name: viewMatch[1], section: currentSection, domain: currentDomain });
  }

  return { tables, views };
}

function phpStringArray(items, indent = '        ') {
  if (!items.length) return '[]';
  return `[\n${items.map((v) => `${indent}'${v.replace(/'/g, "\\'")}',`).join('\n')}\n    ]`;
}

function castForColumn(column) {
  const t = column.sqlType;
  if (t === 'JSON') return 'array';
  if (['DATETIME', 'TIMESTAMP'].includes(t)) return 'immutable_datetime';
  if (t === 'DATE') return 'date';
  if (t === 'TINYINT' && String(column.length || '').trim() === '1') return 'boolean';
  if (['INT', 'INTEGER', 'BIGINT', 'SMALLINT', 'MEDIUMINT'].includes(t)) return 'integer';
  if (['FLOAT', 'DOUBLE', 'REAL'].includes(t)) return 'float';
  if (['DECIMAL', 'NUMERIC'].includes(t)) {
    const scale = String(column.length || '').split(',')[1]?.trim() || '2';
    return `decimal:${scale}`;
  }
  return null;
}

function tsType(column) {
  const t = column.sqlType;
  let base = 'string';
  if (t === 'JSON') base = 'Record<string, unknown> | unknown[]';
  else if (t === 'TINYINT' && String(column.length || '').trim() === '1') base = 'boolean';
  else if (['INT', 'INTEGER', 'BIGINT', 'SMALLINT', 'MEDIUMINT', 'FLOAT', 'DOUBLE', 'REAL', 'DECIMAL', 'NUMERIC'].includes(t)) base = 'number';
  return column.nullable ? `${base} | null` : base;
}

function lcfirst(s) { return s.charAt(0).toLowerCase() + s.slice(1); }
function relationMethodName(column) {
  const raw = column.endsWith('Id') ? column.slice(0, -2) : column;
  const safe = raw.replace(/[^A-Za-z0-9_]/g, '');
  return lcfirst(safe || 'relasi');
}

function buildModelMap(schema) {
  const map = new Map();
  for (const table of schema.tables) {
    map.set(table.name, `App\\Domain\\${table.domain}\\Infrastructure\\Persistence\\Models\\${table.name}`);
  }
  return map;
}

function renderModel(table, modelMap) {
  const namespace = `App\\Domain\\${table.domain}\\Infrastructure\\Persistence\\Models`;
  const hasSoftDelete = table.columns.some((c) => c.name === 'DihapusPada');
  const hasOrg = table.columns.some((c) => c.name === 'OrganisasiId');
  const hasCreated = table.columns.some((c) => c.name === 'DibuatPada');
  const hasUpdated = table.columns.some((c) => c.name === 'DiperbaruiPada');
  const isUser = table.name === 'Pengguna';
  const fillable = table.columns
    .map((c) => c.name)
    .filter((n) => !['Id', 'DibuatPada', 'DiperbaruiPada', 'DihapusPada'].includes(n));
  const casts = table.columns
    .map((c) => [c.name, castForColumn(c)])
    .filter(([, cast]) => Boolean(cast));
  if (isUser) casts.push(['KataSandi', 'hashed']);

  const imports = new Set();
  if (isUser) {
    imports.add('Illuminate\\Foundation\\Auth\\User as Authenticatable');
    imports.add('Illuminate\\Notifications\\Notifiable');
    imports.add('Illuminate\\Database\\Eloquent\\Concerns\\HasUlids');
  } else {
    imports.add('App\\Shared\\Infrastructure\\Persistence\\ModelDasar');
  }
  if (hasSoftDelete) imports.add('Illuminate\\Database\\Eloquent\\SoftDeletes');
  if (hasOrg && !isUser) imports.add('App\\Core\\Organisasi\\MilikOrganisasi');
  if (table.foreignKeys.length) imports.add('Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');

  const traits = [];
  if (isUser) traits.push('HasUlids', 'Notifiable');
  if (hasSoftDelete) traits.push('SoftDeletes');
  if (hasOrg && !isUser) traits.push('MilikOrganisasi');

  const parent = isUser ? 'Authenticatable' : 'ModelDasar';
  const lines = [];
  lines.push('<?php', '', 'declare(strict_types=1);', '', `namespace ${namespace};`, '');
  for (const imp of [...imports].sort()) lines.push(`use ${imp};`);
  lines.push('', `final class ${table.name} extends ${parent}`, '{');
  if (traits.length) lines.push(`    use ${traits.join(', ')};`, '');

  if (isUser) {
    lines.push("    protected $table = 'Pengguna';");
    lines.push("    protected $primaryKey = 'Id';");
    lines.push("    protected $keyType = 'string';");
    lines.push('    public $incrementing = false;', '');
  } else {
    lines.push(`    protected $table = '${table.name}';`, '');
  }

  if (hasCreated && hasUpdated) {
    lines.push("    public const CREATED_AT = 'DibuatPada';");
    lines.push("    public const UPDATED_AT = 'DiperbaruiPada';");
  } else {
    lines.push('    public $timestamps = false;');
  }
  if (hasSoftDelete) lines.push("    public const DELETED_AT = 'DihapusPada';");
  lines.push('');

  lines.push('    protected $fillable = ' + phpStringArray(fillable) + ';', '');

  if (casts.length) {
    lines.push('    protected function casts(): array', '    {', '        return [');
    for (const [name, cast] of casts) lines.push(`            '${name}' => '${cast}',`);
    lines.push('        ];', '    }', '');
  }

  if (isUser) {
    lines.push("    protected $hidden = ['KataSandi', 'TokenIngat'];", '');
    lines.push("    public function getAuthPasswordName(): string", '    {', "        return 'KataSandi';", '    }', '');
    lines.push('    public function getAuthPassword(): string', '    {', "        return (string) $this->KataSandi;", '    }', '');
    lines.push('    public function getRememberTokenName(): string', '    {', "        return 'TokenIngat';", '    }', '');
    lines.push('    public function routeNotificationForMail(): ?string', '    {', "        return $this->Email ?: null;", '    }', '');
  }

  const usedMethods = new Set();
  for (const fk of table.foreignKeys) {
    let method = relationMethodName(fk.column);
    if (usedMethods.has(method)) method = `${method}${fk.targetTable}`;
    usedMethods.add(method);
    const targetClass = modelMap.get(fk.targetTable);
    if (!targetClass) continue;
    lines.push(`    public function ${method}(): BelongsTo`, '    {');
    lines.push(`        return $this->belongsTo(\\${targetClass}::class, '${fk.column}', '${fk.targetColumn}');`);
    lines.push('    }', '');
  }

  const inverse = [];
  for (const [sourceTable, sourceClass] of modelMap.entries()) {
    // Inverse relations are generated from schema in generateDomainStructure only through belongsTo;
    // explicit hasMany relations should be added when business semantics are clear.
    void sourceTable;
    void sourceClass;
  }

  lines.push('}', '');
  return lines.join('\n');
}

function generateDomainStructure(schema) {
  const domains = [...new Set(schema.tables.map((t) => t.domain))].sort();
  const modelMap = buildModelMap(schema);

  for (const domain of domains) {
    const base = `app/Domain/${domain}`;
    [
      'Application/Actions', 'Application/Commands', 'Application/DTO', 'Application/Queries',
      'Application/Services', 'Domain/Events', 'Domain/Exceptions', 'Domain/Policies',
      'Domain/Repositories', 'Domain/Rules', 'Domain/ValueObjects',
      'Infrastructure/Persistence/Models', 'Infrastructure/Persistence/QueryBuilders',
      'Infrastructure/Persistence/Repositories', 'Infrastructure/Services',
      'Http/Controllers', 'Http/Requests', 'Http/Resources', 'Http/Policies',
      'Jobs', 'Listeners', 'Notifications', 'Support',
    ].forEach((p) => touchKeep(`${base}/${p}`));

    write(`${base}/README.md`, `# Domain ${domain}\n\nBounded context Amanpoll untuk ${domain}.\n\n## Aturan\n\n- Controller hanya menangani HTTP boundary.\n- Request menangani validasi input.\n- Action menangani satu use-case.\n- Service menangani orkestrasi lintas use-case dalam domain yang sama.\n- Query dipisahkan dari write path untuk laporan/pencarian berat.\n- Repository interface berada di Domain, implementasi Eloquent di Infrastructure.\n- Event domain tidak boleh bergantung pada Inertia/HTTP.\n- Semua query tabel yang memiliki OrganisasiId wajib menghormati scope organisasi.\n`);

    write(`${base}/routes.php`, `<?php\n\ndeclare(strict_types=1);\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::middleware(['web', 'auth', 'organisasi'])\n    ->prefix('${lcfirst(domain).replace(/([A-Z])/g, '-$1').toLowerCase()}')\n    ->name('${lcfirst(domain)}.')\n    ->group(function (): void {\n        // Route domain ${domain}. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.\n    });\n`);

    touchKeep(`tests/Feature/Domain/${domain}`);
    touchKeep(`tests/Unit/Domain/${domain}`);
  }

  for (const table of schema.tables) {
    const base = `app/Domain/${table.domain}`;
    write(`${base}/Infrastructure/Persistence/Models/${table.name}.php`, renderModel(table, modelMap));
    write(`${base}/Application/DTO/${table.name}Data.php`, renderEntityDto(table));
    write(`${base}/Domain/Repositories/${table.name}Repository.php`, renderRepositoryContract(table));
    write(`${base}/Infrastructure/Persistence/Repositories/Eloquent${table.name}Repository.php`, renderRepositoryImplementation(table));
    write(`${base}/Http/Requests/Simpan${table.name}Request.php`, renderRequest(table));
    write(`${base}/Http/Resources/${table.name}Resource.php`, renderResource(table));
  }

  write('app/Providers/RepositoryServiceProvider.php', renderRepositoryServiceProvider(schema));
  return { domains, modelMap };
}

function renderRepositoryServiceProvider(schema) {
  const lines = [
    '<?php', '', 'declare(strict_types=1);', '', 'namespace App\\Providers;', '',
    'use Illuminate\\Support\\ServiceProvider;', '',
    'final class RepositoryServiceProvider extends ServiceProvider', '{',
    '    public array $bindings = [',
  ];

  for (const table of schema.tables) {
    lines.push(`        \\App\\Domain\\${table.domain}\\Domain\\Repositories\\${table.name}Repository::class => \\App\\Domain\\${table.domain}\\Infrastructure\\Persistence\\Repositories\\Eloquent${table.name}Repository::class,`);
  }

  lines.push('    ];', '}', '');
  return lines.join('\n');
}

function renderEntityDto(table) {
  const namespace = `App\\Domain\\${table.domain}\\Application\\DTO`;
  const fields = table.columns.filter((c) => !['DibuatPada', 'DiperbaruiPada', 'DihapusPada'].includes(c.name));
  const lines = ['<?php', '', 'declare(strict_types=1);', '', `namespace ${namespace};`, '', `final readonly class ${table.name}Data`, '{'];
  lines.push('    public function __construct(');
  for (const col of fields) lines.push(`        public mixed $${col.name} = null,`);
  lines.push('    ) {}', '', '    public static function dariArray(array $data): self', '    {', '        return new self(');
  for (const col of fields) lines.push(`            ${col.name}: $data['${col.name}'] ?? null,`);
  lines.push('        );', '    }', '', '    public function keArray(): array', '    {', '        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);', '    }', '}', '');
  return lines.join('\n');
}

function renderRepositoryContract(table) {
  const ns = `App\\Domain\\${table.domain}\\Domain\\Repositories`;
  const model = `App\\Domain\\${table.domain}\\Infrastructure\\Persistence\\Models\\${table.name}`;
  return `<?php\n\ndeclare(strict_types=1);\n\nnamespace ${ns};\n\nuse ${model};\nuse Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;\n\ninterface ${table.name}Repository\n{\n    public function temukan(string $id): ?${table.name};\n    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;\n    public function simpan(${table.name} $model): ${table.name};\n    public function hapus(${table.name} $model): void;\n}\n`;
}

function renderRepositoryImplementation(table) {
  const ns = `App\\Domain\\${table.domain}\\Infrastructure\\Persistence\\Repositories`;
  const modelNs = `App\\Domain\\${table.domain}\\Infrastructure\\Persistence\\Models\\${table.name}`;
  const contractNs = `App\\Domain\\${table.domain}\\Domain\\Repositories\\${table.name}Repository`;
  const isUser = table.name === 'Pengguna';
  const contextUse = isUser ? `\nuse App\\Core\\Organisasi\\KonteksOrganisasi;` : '';
  const constructor = isUser ? `\n    public function __construct(private readonly KonteksOrganisasi $konteks) {}\n` : '';
  const findQuery = isUser
    ? `${table.name}::query()->where('OrganisasiId', $this->konteks->wajibId())->find($id)`
    : `${table.name}::query()->find($id)`;
  const pageQuery = isUser
    ? `${table.name}::query()->where('OrganisasiId', $this->konteks->wajibId())`
    : `${table.name}::query()`;

  return `<?php\n\ndeclare(strict_types=1);\n\nnamespace ${ns};\n${contextUse}\nuse ${contractNs};\nuse ${modelNs};\nuse Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;\n\nfinal class Eloquent${table.name}Repository implements ${table.name}Repository\n{${constructor}\n    public function temukan(string $id): ?${table.name}\n    {\n        return ${findQuery};\n    }\n\n    public function paginasi(int $perHalaman = 25): LengthAwarePaginator\n    {\n        return ${pageQuery}->latest('${table.columns.some(c => c.name === 'DibuatPada') ? 'DibuatPada' : 'Id'}')->paginate($perHalaman);\n    }\n\n    public function simpan(${table.name} $model): ${table.name}\n    {\n        $model->save();\n        return $model->refresh();\n    }\n\n    public function hapus(${table.name} $model): void\n    {\n        $model->delete();\n    }\n}\n`;
}

function renderRequest(table) {
  const ns = `App\\Domain\\${table.domain}\\Http\\Requests`;
  const rules = table.columns
    .filter((c) => !['Id', 'DibuatPada', 'DiperbaruiPada', 'DihapusPada'].includes(c.name))
    .map((c) => `            '${c.name}' => ['${c.nullable ? 'nullable' : 'sometimes'}'],`)
    .join('\n');
  return `<?php\n\ndeclare(strict_types=1);\n\nnamespace ${ns};\n\nuse Illuminate\\Foundation\\Http\\FormRequest;\n\nfinal class Simpan${table.name}Request extends FormRequest\n{\n    public function authorize(): bool\n    {\n        return $this->user() !== null;\n    }\n\n    public function rules(): array\n    {\n        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.\n        return [\n${rules}\n        ];\n    }\n}\n`;
}

function renderResource(table) {
  const ns = `App\\Domain\\${table.domain}\\Http\\Resources`;
  return `<?php\n\ndeclare(strict_types=1);\n\nnamespace ${ns};\n\nuse Illuminate\\Http\\Request;\nuse Illuminate\\Http\\Resources\\Json\\JsonResource;\n\nfinal class ${table.name}Resource extends JsonResource\n{\n    public function toArray(Request $request): array\n    {\n        return parent::toArray($request);\n    }\n}\n`;
}

function renderTypeScriptSchema(schema) {
  const lines = [
    '/* AUTO-GENERATED oleh index.js Amanpoll. Jangan edit manual. */',
    '',
  ];
  for (const table of schema.tables) {
    lines.push(`export interface ${table.name} {`);
    for (const col of table.columns) lines.push(`  ${col.name}: ${tsType(col)};`);
    lines.push('}', '');
  }
  if (schema.views.length) {
    lines.push('export type NamaViewAmanpoll =');
    schema.views.forEach((v, i) => lines.push(`  ${i === 0 ? '' : '| '}'${v.name}'${i === schema.views.length - 1 ? ';' : ''}`));
    lines.push('');
  }
  return lines.join('\n');
}

function renderSchemaMap(schema) {
  const byDomain = {};
  for (const t of schema.tables) (byDomain[t.domain] ||= []).push(t.name);
  const lines = ['# Peta Schema Amanpoll', '', `Total tabel: **${schema.tables.length}**`, ''];
  for (const [domain, tables] of Object.entries(byDomain)) {
    lines.push(`## ${domain}`, '', ...tables.map((x) => `- \`${x}\``), '');
  }
  if (schema.views.length) lines.push('## View', '', ...schema.views.map((v) => `- \`${v.name}\``), '');
  return lines.join('\n');
}

function scaffoldBackend(schemaInfo) {
  write('app/Shared/Infrastructure/Persistence/ModelDasar.php', `<?php

declare(strict_types=1);

namespace App\\Shared\\Infrastructure\\Persistence;

use Illuminate\\Database\\Eloquent\\Concerns\\HasUlids;
use Illuminate\\Database\\Eloquent\\Model;

abstract class ModelDasar extends Model
{
    use HasUlids;

    protected $primaryKey = 'Id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function getRouteKeyName(): string
    {
        return 'Id';
    }
}
`);

  write('app/Core/Organisasi/KonteksOrganisasi.php', `<?php

declare(strict_types=1);

namespace App\\Core\\Organisasi;

use LogicException;

final class KonteksOrganisasi
{
    private ?string $organisasiId = null;

    public function tetapkan(?string $organisasiId): void
    {
        $this->organisasiId = $organisasiId;
    }

    public function id(): ?string
    {
        return $this->organisasiId;
    }

    public function wajibId(): string
    {
        return $this->organisasiId ?? throw new LogicException('Konteks organisasi belum ditetapkan.');
    }

    public function ada(): bool
    {
        return $this->organisasiId !== null;
    }

    public function bersihkan(): void
    {
        $this->organisasiId = null;
    }
}
`);

  write('app/Core/Organisasi/ScopeOrganisasi.php', `<?php

declare(strict_types=1);

namespace App\\Core\\Organisasi;

use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Scope;

final class ScopeOrganisasi implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $konteks = app(KonteksOrganisasi::class);

        if (!$konteks->ada()) {
            // Fail-closed: query tenant tidak boleh lintas organisasi secara tidak sengaja.
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->qualifyColumn('OrganisasiId'), $konteks->wajibId());
    }
}
`);

  write('app/Core/Organisasi/MilikOrganisasi.php', `<?php

declare(strict_types=1);

namespace App\\Core\\Organisasi;

use Illuminate\\Database\\Eloquent\\Model;

trait MilikOrganisasi
{
    protected static function bootMilikOrganisasi(): void
    {
        static::addGlobalScope(new ScopeOrganisasi());

        static::creating(function (Model $model): void {
            if (!empty($model->OrganisasiId)) {
                return;
            }

            $konteks = app(KonteksOrganisasi::class);
            if ($konteks->ada()) {
                $model->OrganisasiId = $konteks->wajibId();
            }
        });
    }
}
`);

  write('app/Core/Izin/PemeriksaIzin.php', `<?php

declare(strict_types=1);

namespace App\\Core\\Izin;

use App\\Core\\Organisasi\\KonteksOrganisasi;
use Illuminate\\Contracts\\Cache\\Repository as CacheRepository;
use Illuminate\\Support\\Facades\\DB;

final class PemeriksaIzin
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly KonteksOrganisasi $konteksOrganisasi,
    ) {}

    public function boleh(string $penggunaId, string $kodeIzin): bool
    {
        $organisasiId = $this->konteksOrganisasi->id();
        if (!$organisasiId) return false;

        $key = "izin:{$organisasiId}:{$penggunaId}:{$kodeIzin}";

        return (bool) $this->cache->remember($key, now()->addMinutes(5), function () use ($penggunaId, $kodeIzin, $organisasiId): bool {
            return DB::table('PenggunaPeran as pp')
                ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
                ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
                ->where('pp.OrganisasiId', $organisasiId)
                ->where('pp.PenggunaId', $penggunaId)
                ->where('i.Kode', $kodeIzin)
                ->where(function ($q): void {
                    $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now());
                })
                ->where(function ($q): void {
                    $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now());
                })
                ->exists();
        });
    }
}
`);

  write('app/Http/Middleware/TetapkanKonteksOrganisasi.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Middleware;

use App\\Core\\Organisasi\\KonteksOrganisasi;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

final class TetapkanKonteksOrganisasi
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna?->OrganisasiId) {
            $this->konteks->tetapkan((string) $pengguna->OrganisasiId);
        }

        try {
            return $next($request);
        } finally {
            $this->konteks->bersihkan();
        }
    }
}
`);

  write('app/Http/Middleware/PastikanMemilikiIzin.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Middleware;

use App\\Core\\Izin\\PemeriksaIzin;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

final class PastikanMemilikiIzin
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function handle(Request $request, Closure $next, string $kodeIzin): Response
    {
        $pengguna = $request->user();
        abort_unless($pengguna, 401);
        abort_unless($this->izin->boleh((string) $pengguna->Id, $kodeIzin), 403);

        return $next($request);
    }
}
`);

  write('app/Http/Middleware/AutentikasiKunciApi.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Middleware;

use App\\Core\\Organisasi\\KonteksOrganisasi;
use Closure;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Symfony\\Component\\HttpFoundation\\Response;

final class AutentikasiKunciApi
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && str_contains($token, '.'), 401, 'Kunci API tidak valid.');

        [$prefix] = explode('.', $token, 2);
        $kunci = DB::table('KunciApi')
            ->where('AwalanKunci', $prefix)
            ->where('Status', 'Aktif')
            ->first();

        abort_unless($kunci, 401, 'Kunci API tidak valid.');
        abort_if($kunci->KadaluarsaPada && now()->greaterThan($kunci->KadaluarsaPada), 401, 'Kunci API kedaluwarsa.');
        abort_unless(hash_equals((string) $kunci->HashKunci, hash('sha256', $token)), 401, 'Kunci API tidak valid.');

        $ipDiizinkan = $kunci->AlamatIpDiizinkan ? json_decode((string) $kunci->AlamatIpDiizinkan, true) : null;
        if (is_array($ipDiizinkan) && $ipDiizinkan !== []) {
            abort_unless(in_array($request->ip(), $ipDiizinkan, true), 403, 'Alamat IP tidak diizinkan.');
        }

        $this->konteks->tetapkan((string) $kunci->OrganisasiId);
        $request->attributes->set('KunciApiId', (string) $kunci->Id);
        $request->attributes->set('CakupanKunciApi', $kunci->Cakupan ? json_decode((string) $kunci->Cakupan, true) : []);

        try {
            return $next($request);
        } finally {
            $this->konteks->bersihkan();
        }
    }
}
`);

  write('app/Http/Middleware/HandleInertiaRequests.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Middleware;

use Illuminate\\Http\\Request;
use Inertia\\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'namaAplikasi' => config('app.name'),
            'auth' => [
                'pengguna' => $request->user(),
            ],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'gagal' => fn () => $request->session()->get('gagal'),
            ],
        ];
    }
}
`);

  write('app/Providers/AmanpollServiceProvider.php', `<?php

declare(strict_types=1);

namespace App\\Providers;

use App\\Core\\Organisasi\\KonteksOrganisasi;
use Illuminate\\Support\\ServiceProvider;

final class AmanpollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KonteksOrganisasi::class, fn () => new KonteksOrganisasi());
        $this->app->bind(
            \\App\\Shared\\Domain\\Contracts\\TransaksiDatabase::class,
            \\App\\Shared\\Infrastructure\\Persistence\\TransaksiDatabaseLaravel::class,
        );
    }

    public function boot(): void
    {
        $zonaWaktu = (string) config('amanpoll.zona_waktu_default', 'Asia/Jakarta');
        config(['app.timezone' => $zonaWaktu]);
        date_default_timezone_set($zonaWaktu);

        // Binding repository spesifik domain ditambahkan ketika use-case mulai diimplementasikan.
    }
}
`);

  write('app/Providers/DomainServiceProvider.php', `<?php

declare(strict_types=1);

namespace App\\Providers;

use Illuminate\\Support\\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (glob(app_path('Domain/*/routes.php')) ?: [] as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }
    }
}
`);

  write('app/Http/Controllers/Auth/LoginController.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Auth;

use App\\Core\\Organisasi\\KonteksOrganisasi;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;
use Inertia\\Inertia;
use Inertia\\Response;

final class LoginController extends Controller
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'KodeOrganisasi' => ['required', 'string', 'max:50'],
            'Email' => ['required', 'email'],
            'KataSandi' => ['required', 'string'],
        ]);

        $organisasi = DB::table('Organisasi')
            ->where('Kode', $data['KodeOrganisasi'])
            ->where('Status', 'Aktif')
            ->first(['Id']);

        if (!$organisasi) {
            throw ValidationException::withMessages([
                'Email' => 'Organisasi, email, atau kata sandi tidak sesuai.',
            ]);
        }

        $this->konteks->tetapkan((string) $organisasi->Id);

        $berhasil = Auth::attempt(
            ['OrganisasiId' => (string) $organisasi->Id, 'Email' => $data['Email'], 'password' => $data['KataSandi'], 'Status' => 'Aktif'],
            $request->boolean('IngatSaya'),
        );

        if (!$berhasil) {
            $this->konteks->bersihkan();
            throw ValidationException::withMessages([
                'Email' => 'Organisasi, email, atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        DB::table('Pengguna')->where('Id', $request->user()->Id)->update(['TerakhirMasukPada' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
`);

  write('app/Http/Controllers/DashboardController.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Controllers;

use Inertia\\Inertia;
use Inertia\\Response;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard/Index');
    }
}
`);

  write('app/Http/Controllers/Api/StatusController.php', `<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\JsonResponse;

final class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'aplikasi' => config('app.name'),
            'status' => 'ok',
            'versi' => '1.0.0',
            'waktu' => now()->toIso8601String(),
        ]);
    }
}
`);

  if (schemaInfo) {
    generateDomainStructure(schemaInfo);
    write('resources/js/types/generated/Database.ts', renderTypeScriptSchema(schemaInfo));
    write('docs/PETA-SCHEMA.md', renderSchemaMap(schemaInfo));
  }
}

function scaffoldConfig(schemaInfo) {
  const userModel = schemaInfo
    ? `App\\Domain\\Platform\\Infrastructure\\Persistence\\Models\\Pengguna::class`
    : `App\\Models\\User::class`;

  write('config/amanpoll.php', `<?php

return [
    'header_organisasi' => env('AMANPOLL_HEADER_ORGANISASI', 'X-Organisasi-Id'),
    'audit_aktif' => env('AMANPOLL_AUDIT_AKTIF', true),
    'mata_uang' => env('AMANPOLL_MATA_UANG', 'IDR'),
    'zona_waktu_default' => env('AMANPOLL_ZONA_WAKTU', 'Asia/Jakarta'),
];
`);

  write('config/auth.php', `<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => 'pengguna',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'pengguna',
        ],
    ],

    'providers' => [
        'pengguna' => [
            'driver' => 'eloquent',
            'model' => ${userModel},
        ],
    ],

    'passwords' => [
        'pengguna' => [
            'provider' => 'pengguna',
            'table' => 'TokenResetKataSandi',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
`);

  write('bootstrap/providers.php', `<?php

return [
    App\\Providers\\AppServiceProvider::class,
    App\\Providers\\AmanpollServiceProvider::class,
    App\\Providers\\DomainServiceProvider::class,${schemaInfo ? "\n    App\\Providers\\RepositoryServiceProvider::class," : ''}
];
`);

  write('bootstrap/app.php', `<?php

use App\\Http\\Middleware\\HandleInertiaRequests;
use App\\Http\\Middleware\\PastikanMemilikiIzin;
use App\\Http\\Middleware\\TetapkanKonteksOrganisasi;
use Illuminate\\Foundation\\Application;
use Illuminate\\Foundation\\Configuration\\Exceptions;
use Illuminate\\Foundation\\Configuration\\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);

        $middleware->alias([
            'organisasi' => TetapkanKonteksOrganisasi::class,
            'izin' => PastikanMemilikiIzin::class,
            'kunci.api' => \\App\\Http\\Middleware\\AutentikasiKunciApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Mapping exception domain/API dapat ditambahkan di sini.
    })
    ->create();
`);
}

function scaffoldRoutes() {
  write('routes/web.php', `<?php

use App\\Http\\Controllers\\Auth\\LoginController;
use App\\Http\\Controllers\\DashboardController;
use Illuminate\\Support\\Facades\\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'organisasi'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
`);

  write('routes/api.php', `<?php

use App\\Http\\Controllers\\Api\\StatusController;
use Illuminate\\Support\\Facades\\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/status', StatusController::class)->name('api.status');

    Route::middleware('kunci.api')->group(function (): void {
        // Endpoint integrasi Amanpoll yang membutuhkan API key ditempatkan di sini.
    });
});
`);

}

function scaffoldFrontend() {
  write('vite.config.ts', `import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      refresh: true,
    }),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
});
`);

  write('tsconfig.json', `{
  "compilerOptions": {
    "target": "ES2022",
    "useDefineForClassFields": true,
    "lib": ["ES2022", "DOM", "DOM.Iterable"],
    "allowJs": false,
    "skipLibCheck": true,
    "esModuleInterop": true,
    "allowSyntheticDefaultImports": true,
    "strict": true,
    "forceConsistentCasingInFileNames": true,
    "module": "ESNext",
    "moduleResolution": "Bundler",
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "baseUrl": ".",
    "paths": {
      "@/*": ["resources/js/*"]
    }
  },
  "include": ["resources/js/**/*.ts", "resources/js/**/*.tsx", "vite.config.ts"]
}
`);

  write('components.json', `{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "new-york",
  "rsc": false,
  "tsx": true,
  "tailwind": {
    "config": "",
    "css": "resources/css/app.css",
    "baseColor": "neutral",
    "cssVariables": true,
    "prefix": ""
  },
  "iconLibrary": "lucide",
  "aliases": {
    "components": "@/components",
    "utils": "@/lib/utils",
    "ui": "@/components/ui",
    "lib": "@/lib",
    "hooks": "@/hooks"
  }
}
`);

  write('resources/css/app.css', `@import "tailwindcss";

:root {
  --background: 0 0% 100%;
  --foreground: 0 0% 3.9%;
  --card: 0 0% 100%;
  --card-foreground: 0 0% 3.9%;
  --popover: 0 0% 100%;
  --popover-foreground: 0 0% 3.9%;
  --primary: 0 0% 9%;
  --primary-foreground: 0 0% 98%;
  --secondary: 0 0% 96.1%;
  --secondary-foreground: 0 0% 9%;
  --muted: 0 0% 96.1%;
  --muted-foreground: 0 0% 45.1%;
  --accent: 0 0% 96.1%;
  --accent-foreground: 0 0% 9%;
  --destructive: 0 84.2% 60.2%;
  --destructive-foreground: 0 0% 98%;
  --border: 0 0% 89.8%;
  --input: 0 0% 89.8%;
  --ring: 0 0% 3.9%;
  --radius: 0.625rem;
}

* { border-color: hsl(var(--border)); }
body { background: hsl(var(--background)); color: hsl(var(--foreground)); }
`);

  write('resources/views/app.blade.php', `<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Amanpoll') }}</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="min-h-screen antialiased">
    @inertia
</body>
</html>
`);

  write('resources/js/lib/utils.ts', `import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
`);

  write('resources/js/types/global.d.ts', `export interface PenggunaAuth {
  Id: string;
  Nama: string;
  Email: string;
  OrganisasiId: string;
}

export interface PageProps {
  namaAplikasi: string;
  auth: { pengguna: PenggunaAuth | null };
  flash: { sukses?: string | null; gagal?: string | null };
  [key: string]: unknown;
}
`);

  write('resources/js/app.tsx', `import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

createInertiaApp({
  title: (title) => (title ? title + ' - Amanpoll' : 'Amanpoll'),
  resolve: (name) =>
    resolvePageComponent('./pages/' + name + '.tsx', import.meta.glob('./pages/**/*.tsx')),
  setup({ el, App, props }) {
    createRoot(el).render(
      <>
        <App {...props} />
        <Toaster richColors position="top-right" />
      </>,
    );
  },
  progress: { color: '#18181b' },
});
`);

  write('resources/js/components/ui/button.tsx', `import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
  {
    variants: {
      variant: {
        default: 'bg-zinc-900 text-white hover:bg-zinc-800',
        outline: 'border bg-white hover:bg-zinc-50',
        ghost: 'hover:bg-zinc-100',
        destructive: 'bg-red-600 text-white hover:bg-red-500',
      },
      size: {
        default: 'h-9 px-4 py-2',
        sm: 'h-8 px-3',
        lg: 'h-10 px-6',
        icon: 'size-9',
      },
    },
    defaultVariants: { variant: 'default', size: 'default' },
  },
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    return <Comp ref={ref} className={cn(buttonVariants({ variant, size }), className)} {...props} />;
  },
);
Button.displayName = 'Button';
`);

  write('resources/js/components/ui/card.tsx', `import * as React from 'react';
import { cn } from '@/lib/utils';

export function Card({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('rounded-xl border bg-white shadow-sm', className)} {...props} />;
}
export function CardHeader({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('p-6 pb-2', className)} {...props} />;
}
export function CardTitle({ className, ...props }: React.HTMLAttributes<HTMLHeadingElement>) {
  return <h3 className={cn('font-semibold tracking-tight', className)} {...props} />;
}
export function CardContent({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('p-6 pt-4', className)} {...props} />;
}
`);

  write('resources/js/layouts/AppLayout.tsx', `import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { PageProps } from '@/types/global';

const menu = [
  ['Dashboard', '/'], ['Aset', '/aset'], ['Perintah Kerja', '/pemeliharaan'],
  ['Kalibrasi', '/kalibrasi'], ['Persediaan', '/persediaan'],
  ['Pengadaan', '/perencanaan-pengadaan'], ['Laporan', '/pelaporan'],
];

export default function AppLayout({ children }: PropsWithChildren) {
  const { auth } = usePage<PageProps>().props;
  return (
    <div className="min-h-screen bg-zinc-50">
      <div className="flex min-h-screen">
        <aside className="hidden w-64 border-r bg-white p-5 lg:block">
          <div className="mb-8 text-xl font-semibold">Amanpoll</div>
          <nav className="space-y-1">
            {menu.map(([label, href]) => (
              <Link key={href} href={href} className="block rounded-md px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">
                {label}
              </Link>
            ))}
          </nav>
        </aside>
        <main className="min-w-0 flex-1">
          <header className="flex h-16 items-center justify-between border-b bg-white px-6">
            <div className="font-medium">Asset & Maintenance Management</div>
            <div className="text-sm text-zinc-600">{auth.pengguna?.Nama ?? ''}</div>
          </header>
          <div className="p-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
`);

  write('resources/js/pages/Dashboard/Index.tsx', `import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const ringkasan = [
  ['Total Aset', '—'], ['Work Order Aktif', '—'], ['PM Jatuh Tempo', '—'], ['Kalibrasi Jatuh Tempo', '—'],
];

export default function Dashboard() {
  return (
    <AppLayout>
      <Head title="Dashboard" />
      <div className="mb-6">
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-zinc-500">Ringkasan operasional Amanpoll.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {ringkasan.map(([label, value]) => (
          <Card key={label}>
            <CardHeader><CardTitle className="text-sm font-medium text-zinc-500">{label}</CardTitle></CardHeader>
            <CardContent><div className="text-3xl font-semibold">{value}</div></CardContent>
          </Card>
        ))}
      </div>
    </AppLayout>
  );
}
`);

  write('resources/js/pages/Auth/Login.tsx', `import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function Login() {
  const form = useForm({ KodeOrganisasi: '', Email: '', KataSandi: '', IngatSaya: false });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/login', { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-zinc-50 p-6">
      <Head title="Masuk" />
      <form onSubmit={submit} className="w-full max-w-sm space-y-5 rounded-xl border bg-white p-6 shadow-sm">
        <div>
          <h1 className="text-2xl font-semibold">Amanpoll</h1>
          <p className="text-sm text-zinc-500">Masuk ke sistem Asset & Maintenance Management.</p>
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium">Kode Organisasi</label>
          <input className="h-10 w-full rounded-md border px-3" value={form.data.KodeOrganisasi} onChange={(e) => form.setData('KodeOrganisasi', e.target.value)} autoComplete="organization" />
          {form.errors.KodeOrganisasi && <p className="text-sm text-red-600">{form.errors.KodeOrganisasi}</p>}
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium">Email</label>
          <input className="h-10 w-full rounded-md border px-3" type="email" value={form.data.Email} onChange={(e) => form.setData('Email', e.target.value)} />
          {form.errors.Email && <p className="text-sm text-red-600">{form.errors.Email}</p>}
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium">Kata Sandi</label>
          <input className="h-10 w-full rounded-md border px-3" type="password" value={form.data.KataSandi} onChange={(e) => form.setData('KataSandi', e.target.value)} />
          {form.errors.KataSandi && <p className="text-sm text-red-600">{form.errors.KataSandi}</p>}
        </div>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={form.data.IngatSaya} onChange={(e) => form.setData('IngatSaya', e.target.checked)} />
          Ingat saya
        </label>
        <Button className="w-full" disabled={form.processing}>Masuk</Button>
      </form>
    </div>
  );
}
`);

  // PRD 14.1: berkas feature dibuat saat ada isinya. Generator hanya menyiapkan
  // halaman; api.ts/types.ts/status.ts/components/hooks menyusul saat feature digarap.
  for (const feature of FRONTEND_FEATURES) {
    touchKeep(`resources/js/features/${feature}/pages`);
    write(`resources/js/features/${feature}/pages/Index.tsx`, `export default function ${feature}Index() {\n  return (\n    <section className=\"space-y-2\">\n      <h1 className=\"text-2xl font-semibold tracking-tight\">${feature}</h1>\n      <p className=\"text-sm text-zinc-500\">Halaman modul ${feature}. Implementasikan use-case dan UI di feature ini.</p>\n    </section>\n  );\n}\n`);
  }
}

function scaffoldInfrastructure() {
  write('database/migrations/2026_01_01_000000_buat_tabel_infrastruktur_laravel.php', `<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Pengguna') && !Schema::hasColumn('Pengguna', 'TokenIngat')) {
            Schema::table('Pengguna', function (Blueprint $table): void {
                $table->string('TokenIngat', 100)->nullable()->after('KataSandi');
            });
        }

        if (!Schema::hasTable('TokenResetKataSandi')) {
            Schema::create('TokenResetKataSandi', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('AntrianPekerjaan')) {
            Schema::create('AntrianPekerjaan', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('KelompokAntrianPekerjaan')) {
            Schema::create('KelompokAntrianPekerjaan', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!Schema::hasTable('PekerjaanGagal')) {
            Schema::create('PekerjaanGagal', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('PekerjaanGagal');
        Schema::dropIfExists('KelompokAntrianPekerjaan');
        Schema::dropIfExists('AntrianPekerjaan');
        Schema::dropIfExists('TokenResetKataSandi');
        if (Schema::hasTable('Pengguna') && Schema::hasColumn('Pengguna', 'TokenIngat')) {
            Schema::table('Pengguna', fn (Blueprint $table) => $table->dropColumn('TokenIngat'));
        }
    }
};
`);

  write('config/queue.php', `<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'sync' => ['driver' => 'sync'],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'AntrianPekerjaan'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => true,
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('DB_JOB_BATCHES_TABLE', 'KelompokAntrianPekerjaan'),
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('DB_FAILED_JOBS_TABLE', 'PekerjaanGagal'),
    ],
];
`);

  write('routes/console.php', `<?php

use Illuminate\\Support\\Facades\\Schedule;

/*
| Shared hosting tidak menjalankan daemon queue secara permanen.
| hPanel hanya perlu memanggil "php artisan schedule:run" setiap menit.
| Scheduler di bawah menjalankan worker pendek lalu keluar dengan aman.
*/
Schedule::command('queue:work database --queue=high,default,low --stop-when-empty --sleep=1 --tries=3 --timeout=45 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(1);

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('02:15')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping();

Schedule::command('auth:clear-resets')
    ->dailyAt('02:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'));
`);

  write('phpstan.neon', `includes:
  - vendor/larastan/larastan/extension.neon

parameters:
  paths:
    - app
  level: 7
  checkMissingIterableValueType: false
`);

  write('.prettierrc', `{
  "singleQuote": true,
  "semi": true,
  "trailingComma": "all",
  "printWidth": 110
}
`);

  write('.editorconfig', `root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
indent_style = space
indent_size = 4
trim_trailing_whitespace = true

[*.{js,jsx,ts,tsx,json,yml,yaml}]
indent_size = 2

[*.md]
trim_trailing_whitespace = false
`);

  write('docs/ARSITEKTUR.md', `# Arsitektur Amanpoll

Amanpoll menggunakan **Laravel 13 + Modular Monolith + DDD pragmatis**.

## Prinsip

- \`app/Core\`: capability lintas-domain (tenancy, IAM, audit, approval, integrasi, berkas, notifikasi, keamanan).
- \`app/Domain/<Domain>\`: bounded context bisnis.
- \`app/Shared\`: abstraksi teknis yang stabil dan benar-benar reusable.
- Controller tipis: validasi -> Action/Service -> Resource/Inertia.
- Business rule tidak ditaruh di Controller, Request, React component, atau Repository.
- Semua tabel dengan \`OrganisasiId\` otomatis memakai scope organisasi.
- ULID adalah identifier bisnis utama.
- Waktu disimpan UTC; presentasi mengikuti zona waktu organisasi.
- Query/reporting berat dipisah dari write path.
- Integrasi eksternal wajib idempotent dan tercatat di audit/outbox.

## Dependency

\`Http -> Application -> Domain <- Infrastructure\`

Tidak semua tabel boleh memperoleh generic CRUD route. Tabel ledger, audit, approval, stok, status history, outbox, idempotensi, dan sinkronisasi hanya dimutasi lewat use-case yang menjaga invariant.
`);

  write('README-AMANPOLL.md', `# Amanpoll

Amanpoll adalah platform CMMS / Asset & Maintenance Management multi-industri.

## Stack

- Laravel 13 (PHP 8.3+, PHP 8.4 direkomendasikan)
- MySQL 8
- Inertia.js + React + TypeScript
- Tailwind CSS 4 + shadcn/ui
- Queue database untuk kompatibilitas shared hosting
- Scheduler Laravel melalui Cron hPanel
- Penyimpanan local/private secara default; S3-compatible opsional

## Development lokal

\`\`\`bash
composer install
npm install
npm run dev
php artisan serve
php artisan queue:work database
\`\`\`

## Production Niagahoster Business

Lihat \`deploy/niagahoster/DEPLOY.md\`. Build Vite dibuat sebelum upload/deploy. Production tidak bergantung pada Redis, Horizon, Reverb, Docker, Supervisor, atau Octane.

Schema utama: \`database/schema/Amanpoll_Database_MySQL.sql\`.
`);

  scaffoldCoreStructure();
  scaffoldDeploymentNiagahoster();
}

function scaffoldCoreStructure() {
  const dirs = [
    'app/Core/IdentitasAkses/Actions', 'app/Core/IdentitasAkses/Services', 'app/Core/IdentitasAkses/Policies',
    'app/Core/Organisasi/Actions', 'app/Core/Organisasi/Services',
    'app/Core/Audit/Contracts', 'app/Core/Audit/Services',
    'app/Core/Persetujuan/Contracts', 'app/Core/Persetujuan/Services',
    'app/Core/Integrasi/Contracts', 'app/Core/Integrasi/Services',
    'app/Core/Notifikasi/Channels', 'app/Core/Notifikasi/Services',
    'app/Core/Berkas/Contracts', 'app/Core/Berkas/Services',
    'app/Core/Sinkronisasi/Contracts', 'app/Core/Sinkronisasi/Services',
    'app/Core/Penomoran/Services', 'app/Core/Keamanan/Services',
    'app/Shared/Application/Bus', 'app/Shared/Application/Pagination',
    'app/Shared/Domain/Contracts', 'app/Shared/Domain/Exceptions', 'app/Shared/Domain/ValueObjects',
    'app/Shared/Infrastructure/Clock', 'app/Shared/Infrastructure/Persistence', 'app/Shared/Infrastructure/Storage',
    'app/Shared/Support', 'app/Console/Commands', 'app/Jobs', 'app/Listeners', 'app/Notifications',
    'resources/js/components/app', 'resources/js/components/data-table', 'resources/js/components/form',
    'resources/js/components/layout', 'resources/js/components/shared', 'resources/js/hooks',
    'resources/js/lib', 'resources/js/services', 'resources/js/stores', 'resources/js/types/generated',
    'resources/js/constants', 'tests/Architecture', 'tests/Feature/Auth', 'tests/Feature/Tenancy',
    'tests/Feature/Api', 'tests/Unit/Core', 'docs/adr', 'docs/api', 'docs/domain', 'docs/operasional',
  ];
  dirs.forEach(touchKeep);

  write('app/Shared/Domain/Contracts/TransaksiDatabase.php', `<?php

declare(strict_types=1);

namespace App\\Shared\\Domain\\Contracts;

interface TransaksiDatabase
{
    public function jalankan(callable $callback): mixed;
}
`);

  write('app/Shared/Infrastructure/Persistence/TransaksiDatabaseLaravel.php', `<?php

declare(strict_types=1);

namespace App\\Shared\\Infrastructure\\Persistence;

use App\\Shared\\Domain\\Contracts\\TransaksiDatabase;
use Illuminate\\Support\\Facades\\DB;

final class TransaksiDatabaseLaravel implements TransaksiDatabase
{
    public function jalankan(callable $callback): mixed
    {
        return DB::transaction($callback, attempts: 3);
    }
}
`);

  write('app/Jobs/PekerjaanOrganisasi.php', `<?php

declare(strict_types=1);

namespace App\\Jobs;

use App\\Core\\Organisasi\\KonteksOrganisasi;

abstract class PekerjaanOrganisasi
{
    public function __construct(public readonly string $OrganisasiId) {}

    protected function dalamKonteksOrganisasi(callable $callback): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->OrganisasiId);
        try {
            return $callback();
        } finally {
            $konteks->bersihkan();
        }
    }
}
`);

  write('app/Shared/Support/Paginasi.php', `<?php

declare(strict_types=1);

namespace App\\Shared\\Support;

final class Paginasi
{
    public static function perHalaman(?int $nilai, int $default = 25, int $maksimum = 100): int
    {
        return min(max($nilai ?? $default, 1), $maksimum);
    }
}
`);
}

function scaffoldDeploymentNiagahoster() {
  write('deploy/niagahoster/public_html/index.php', `<?php

use Illuminate\\Foundation\\Application;
use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));

// Struktur production yang direkomendasikan:
// domains/domain-anda.tld/
// ├── amanpoll/       <- seluruh source Laravel
// └── public_html/    <- HANYA isi folder deploy/niagahoster/public_html
$amanpollRoot = dirname(__DIR__) . '/amanpoll';

if (file_exists($maintenance = $amanpollRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $amanpollRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $amanpollRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
`);

  write('deploy/niagahoster/public_html/.htaccess', `<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

<FilesMatch "^\\.(env|git|htaccess)">
    Require all denied
</FilesMatch>
`);

  write('deploy/niagahoster/.env.production.example', `APP_NAME=Amanpoll
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.domain-anda.tld
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_Amanpoll
DB_USERNAME=u123456789_Amanpoll
DB_PASSWORD=GANTI_PASSWORD_KUAT

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=AntrianPekerjaan
DB_JOB_BATCHES_TABLE=KelompokAntrianPekerjaan
DB_FAILED_JOBS_TABLE=PekerjaanGagal
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Amanpoll

AMANPOLL_HEADER_ORGANISASI=X-Organisasi-Id
AMANPOLL_AUDIT_AKTIF=true
AMANPOLL_MATA_UANG=IDR
AMANPOLL_ZONA_WAKTU=Asia/Jakarta
AMANPOLL_QUEUE_CRON=true
`);

  write('deploy/niagahoster/deploy.sh', `#!/usr/bin/env bash
set -euo pipefail

# Jalankan dari folder source: domains/domain-anda.tld/amanpoll
# Build frontend sebaiknya sudah dibuat di lokal/CI sehingga public/build ikut ter-upload.

if command -v composer2 >/dev/null 2>&1; then
  COMPOSER_BIN="composer2"
else
  COMPOSER_BIN="composer"
fi

$COMPOSER_BIN install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan down --retry=15 || true
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

echo "Deploy Amanpoll selesai."
`);

  write('deploy/niagahoster/cron.sh', `#!/bin/sh

# Edit dua nilai ini sebelum dipakai.
PHP_BIN="/usr/bin/php"
APP_DIR="/home/u123456789/domains/domain-anda.tld/amanpoll"

cd "$APP_DIR" || exit 1
exec "$PHP_BIN" artisan schedule:run
`);

  write('deploy/niagahoster/cron.txt', `# hPanel -> Website -> Kelola/Dashboard -> Cron Jobs -> Custom
# Jadwal: setiap menit. hPanel menampilkan jadwal cron dalam UTC, tetapi Laravel
# menangani timezone task harian melalui konfigurasi Amanpoll.
#
# Gunakan file .sh supaya command hPanel tidak perlu redirect/special character:
/bin/sh /home/u123456789/domains/domain-anda.tld/amanpoll/deploy/niagahoster/cron.sh

# Jika CLI website memakai PHP 8.4, edit PHP_BIN di cron.sh menjadi:
# /opt/alt/php84/usr/bin/php
`);


  write('deploy/niagahoster/DEPLOY.md', `# Deploy Amanpoll ke Niagahoster Business

Target ini sengaja **tidak bergantung pada daemon**. Production menggunakan queue database yang diproses worker pendek melalui Laravel Scheduler + Cron hPanel.

## Struktur direktori

\`\`\`text
/home/u123456789/domains/domain-anda.tld/
├── amanpoll/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   └── build/          # hasil npm run build
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   └── .env
└── public_html/
    ├── build/              # salin public/build ke sini
    ├── index.php
    ├── .htaccess
    ├── favicon.ico
    └── robots.txt
\`\`\`

Jangan meletakkan \`.env\`, \`vendor\`, \`storage\`, atau source Laravel lain di \`public_html\`.

## Langkah deployment pertama

1. Di hPanel, buat database MySQL dan user database.
2. Pilih PHP **8.4 jika tersedia**; Laravel 13 minimum PHP 8.3.
3. Upload source ke folder \`amanpoll/\` satu tingkat di atas \`public_html/\`.
4. Salin template \`deploy/niagahoster/public_html/*\` ke \`public_html/\`.
5. Salin hasil \`public/build/\` ke \`public_html/build/\`.
6. Buat \`.env\` production dari \`deploy/niagahoster/.env.production.example\`.
7. Via SSH, jalankan Composer 2 dan Artisan.

\`\`\`bash
cd ~/domains/domain-anda.tld/amanpoll
composer2 install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan optimize
\`\`\`

Jika Composer melalui SSH memakai versi PHP berbeda dari website, panggil binary PHP yang benar sesuai panel. Contoh umum:

\`\`\`bash
/opt/alt/php84/usr/bin/php /usr/local/bin/composer2 install --no-dev --prefer-dist --optimize-autoloader
\`\`\`

## Database utama

Import \`database/schema/Amanpoll_Schema_Hosting.sql\` satu kali ke database production (phpMyAdmin atau CLI), kemudian jalankan \`php artisan migrate --force\` untuk tabel infrastruktur Laravel dan migration berikutnya.

## Cron

Buat cron hPanel **setiap menit**. Jalankan \`/bin/sh .../deploy/niagahoster/cron.sh\`; template command ada di \`deploy/niagahoster/cron.txt\`. Pendekatan file \.sh\` juga menghindari masalah karakter khusus pada command cron hPanel.

Scheduler Amanpoll akan menjalankan worker database dengan \`--stop-when-empty\`, jadi tidak memerlukan Supervisor/Horizon.

## Frontend

Build production di mesin development/CI:

\`\`\`bash
npm ci
npm run build
\`\`\`

Upload hasil \`public/build\`. Jangan bergantung pada Vite dev server di hosting.

## Permission

Pastikan proses PHP dapat menulis ke:

- \`storage/\`
- \`bootstrap/cache/\`

Gunakan permission paling ketat yang tetap bekerja; jangan menjadikan seluruh project 777.

## Yang sengaja tidak dipakai di shared hosting

- Docker production
- Laravel Horizon
- Laravel Reverb self-hosted
- Supervisor/systemd
- Octane / FrankenPHP long-running server
- Redis sebagai requirement

Jika suatu hari Amanpoll pindah ke VPS/Cloud, queue/realtime dapat dinaikkan ke Redis + Horizon + Reverb tanpa mengubah domain model.
`);
  write('deploy/niagahoster/RELEASE-CHECKLIST.md', `# Checklist Rilis Amanpoll

- [ ] \`php artisan test\` lulus.
- [ ] \`npm run build\` lulus.
- [ ] \`APP_DEBUG=false\` di production.
- [ ] Database backup tersedia.
- [ ] \`Amanpoll_Schema_Hosting.sql\` hanya untuk instalasi awal; update berikutnya melalui migration.
- [ ] Folder \`storage\` dan \`bootstrap/cache\` writable.
- [ ] \`public_html\` tidak berisi \`.env\`, \`vendor\`, source app, atau dump database.
- [ ] Cron \`schedule:run\` berjalan setiap menit.
- [ ] Queue tidak menumpuk.
- [ ] HTTPS aktif.
- [ ] Smoke test login per organisasi, dashboard, upload, dan health endpoint selesai.
`);

}

function scaffoldPackageScripts() {
  const pkgPath = path.join(targetDir, 'package.json');
  if (!exists(pkgPath)) return;
  const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
  pkg.scripts = {
    ...(pkg.scripts || {}),
    dev: 'vite',
    build: 'tsc --noEmit && vite build',
    typecheck: 'tsc --noEmit',
    format: 'prettier --write "resources/js/**/*.{ts,tsx,js,jsx,json}"',
  };
  backupFile(pkgPath);
  fs.writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + '\n');
}

function installShadcnComponents() {
  if (skipInstall || skipShadcn) return;
  if (!commandExists('npx')) {
    warn('npx tidak ditemukan; komponen shadcn tambahan dilewati.');
    return;
  }
  run('npx', [
    '--yes', 'shadcn@latest', 'add',
    'badge', 'card', 'input', 'label', 'textarea', 'separator', 'skeleton',
    'table', 'dialog', 'sheet', 'dropdown-menu', 'select', 'tabs', 'tooltip', 'sonner',
    '--yes', '--overwrite'
  ], {
    cwd: targetDir,
    label: 'Menambahkan komponen shadcn/ui dasar',
    allowFailure: true,
  });
}

function importSql(sqlPath) {
  if (!args['import-sql']) return;
  if (!sqlPath) throw new Error('--import-sql diberikan tetapi file SQL tidak ditemukan.');
  if (!commandExists('mysql')) throw new Error('mysql CLI tidak ditemukan untuk --import-sql. Gunakan phpMyAdmin jika berada di shared hosting.');

  const sql = Buffer.from(sqlTanpaCreateDatabase(fs.readFileSync(sqlPath, 'utf8')), 'utf8');
  const host = String(args['db-host'] || '127.0.0.1');
  const port = String(args['db-port'] || '3306');
  const database = String(args['db-name'] || 'Amanpoll');
  const user = String(args['db-user'] || 'root');
  const password = String(args['db-password'] || '');

  run('mysql', ['-h', host, '-P', port, '-u', user, database], {
    cwd: targetDir,
    label: `Mengimpor schema Amanpoll ke database ${database} @ ${host}:${port}`,
    env: password ? { MYSQL_PWD: password } : {},
    input: sql,
    stdio: ['pipe', 'inherit', 'inherit'],
  });
  ok('Import SQL selesai.');
}

function generateStructureManifest(schemaInfo) {
  const ignored = new Set(['vendor', 'node_modules', '.git', '.amanpoll-backup']);
  const lines = [
    'AMANPOLL - STRUKTUR PROJECT TERGENERASI',
    '======================================',
    '',
    `Hosting profile : ${HOSTING_PROFILE}`,
    `Database tables : ${schemaInfo?.tables.length || 0}`,
    `Database views  : ${schemaInfo?.views.length || 0}`,
    '',
    'Catatan: vendor, node_modules, .git, backup, dan file runtime storage tidak dicantumkan.',
    '',
  ];

  function walk(abs, rel = '') {
    const entries = fs.readdirSync(abs, { withFileTypes: true })
      .filter((entry) => !ignored.has(entry.name))
      .filter((entry) => !(rel === 'storage' && ['framework', 'logs'].includes(entry.name)))
      .sort((a, b) => a.name.localeCompare(b.name, 'id'));

    for (const entry of entries) {
      const nextRel = rel ? `${rel}/${entry.name}` : entry.name;
      if (nextRel === 'docs/STRUKTUR-PROJECT.txt') continue;
      lines.push(entry.isDirectory() ? `${nextRel}/` : nextRel);
      if (entry.isDirectory()) walk(path.join(abs, entry.name), nextRel);
    }
  }

  walk(targetDir);
  write('docs/STRUKTUR-PROJECT.txt', lines.join('\n') + '\n', { overwrite: true });
  ok(`Manifest struktur dibuat: docs/STRUKTUR-PROJECT.txt (${lines.length} baris)`);
}

function finalize(schemaInfo) {
  if (!skipInstall) {
    run('composer', ['dump-autoload'], { cwd: targetDir, label: 'Composer dump-autoload', allowFailure: true });
    run('php', ['artisan', 'optimize:clear'], { cwd: targetDir, label: 'Membersihkan cache Laravel', allowFailure: true });
    run('php', ['artisan', 'migrate', '--pretend'], { cwd: targetDir, label: 'Validasi migration Laravel', allowFailure: true });
  }

  const count = schemaInfo?.tables.length || 0;
  console.log('');
  ok(`Setup Amanpoll selesai di: ${targetDir}`);
  if (count) ok(`${count} Eloquent Model + DTO + Repository + Request + Resource dibuat dari schema SQL.`);
  console.log(`\nDevelopment lokal:\n` +
`  cd ${path.relative(cwd, targetDir) || '.'}\n` +
`  npm run dev\n` +
`  php artisan serve\n` +
`  php artisan queue:work database\n\n` +
`Deployment Niagahoster Business:\n` +
`  baca deploy/niagahoster/DEPLOY.md\n` +
`  build frontend: npm run build\n` +
`  cron production: php artisan schedule:run setiap menit\n`);
}

function main() {
  try {
    const targetAlreadyLaravel = exists(path.join(targetDir, 'artisan')) && exists(path.join(targetDir, 'composer.json'));
    preflight({ needComposer: !targetAlreadyLaravel || !skipInstall });
    createLaravelProject();

    const detectedSql = detectSql();
    const copiedSql = copySql(detectedSql);
    let schemaInfo = null;
    if (copiedSql) {
      schemaInfo = parseSqlSchema(fs.readFileSync(copiedSql, 'utf8'));
      ok(`Schema terbaca: ${schemaInfo.tables.length} tabel, ${schemaInfo.views.length} view.`);
    }

    installDependencies();
    prepareEnv();
    scaffoldBackend(schemaInfo);
    scaffoldConfig(schemaInfo);
    scaffoldRoutes();
    scaffoldFrontend();
    scaffoldInfrastructure();
    scaffoldPackageScripts();
    installShadcnComponents();
    importSql(copiedSql);
    generateStructureManifest(schemaInfo);
    finalize(schemaInfo);
  } catch (error) {
    fail(error instanceof Error ? error.message : String(error));
    process.exit(1);
  }
}

main();
