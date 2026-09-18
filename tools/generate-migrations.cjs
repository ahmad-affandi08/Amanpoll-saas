#!/usr/bin/env node
'use strict';

/**
 * Konversi database/schema/Amanpoll_Database_MySQL.sql menjadi migration
 * Laravel (Schema Builder) database-agnostic, supaya `php artisan migrate`
 * bisa membangun schema yang sama persis di MySQL (produksi) maupun SQLite
 * (test). SQL mentah tetap menjadi dokumentasi/rujukan di database/schema/.
 *
 * Pemakaian:
 *   node tools/generate-migrations.js
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const SQL_PATH = path.join(ROOT, 'database/schema/Amanpoll_Database_MySQL.sql');
const MIGRATIONS_DIR = path.join(ROOT, 'database/migrations');
const SEEDERS_DIR = path.join(ROOT, 'database/seeders');

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
      unsigned: /\bUNSIGNED\b/i.test(definition),
      nullable: /\bNULL\b/i.test(definition) && !/\bNOT NULL\b/i.test(definition),
      useCurrent: /DEFAULT\s+CURRENT_TIMESTAMP/i.test(definition),
      useCurrentOnUpdate: /ON UPDATE CURRENT_TIMESTAMP/i.test(definition),
      defaultLiteral: (() => {
        const dm = definition.match(/DEFAULT\s+'((?:[^'\\]|\\.)*)'/i);
        if (dm) return { type: 'string', value: dm[1].replace(/\\'/g, "'") };
        const dn = definition.match(/DEFAULT\s+(-?\d+(?:\.\d+)?)\b/i);
        if (dn && !/DEFAULT\s+CURRENT_TIMESTAMP/i.test(definition)) return { type: 'number', value: dn[1] };
        return null;
      })(),
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

function parseUniqueKeys(block) {
  const out = [];
  const re = /UNIQUE KEY\s+`([^`]+)`\s*\(([^)]+)\)/gi;
  let m;
  while ((m = re.exec(block)) !== null) {
    const columns = [...m[2].matchAll(/`([^`]+)`/g)].map((c) => c[1]);
    out.push({ name: m[1], columns });
  }
  return out;
}

function parseIndexKeys(block) {
  const out = [];
  const re = /(?<!UNIQUE )\bKEY\s+`([^`]+)`\s*\(([^)]+)\)/gi;
  let m;
  while ((m = re.exec(block)) !== null) {
    const columns = [...m[2].matchAll(/`([^`]+)`/g)].map((c) => c[1]);
    out.push({ name: m[1], columns });
  }
  return out;
}

function parsePrimaryKey(block) {
  const m = block.match(/PRIMARY KEY\s*\(([^)]+)\)/i);
  if (!m) return [];
  return [...m[1].matchAll(/`([^`]+)`/g)].map((c) => c[1]);
}

function parseSqlSchema(sql) {
  const lines = sql.split(/\r?\n/);
  const tables = [];
  const views = [];

  for (let i = 0; i < lines.length; i++) {
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
      tables.push({
        name,
        columns: parseColumns(block),
        foreignKeys: parseForeignKeys(block),
        uniqueKeys: parseUniqueKeys(block),
        indexKeys: parseIndexKeys(block),
        primaryKeys: parsePrimaryKey(block),
      });
    }

    const viewMatch = lines[i].match(/^CREATE OR REPLACE VIEW\s+`([^`]+)`\s+AS/i);
    if (viewMatch) {
      const name = viewMatch[1];
      const body = [lines[i]];
      i++;
      while (i < lines.length && lines[i].trim() !== '' && !/^CREATE /i.test(lines[i])) {
        body.push(lines[i]);
        i++;
      }
      i--;
      views.push({ name, sql: body.join('\n').replace(/;\s*$/, '') });
    }
  }

  return { tables, views };
}

function parseIzinSeed(sql) {
  const m = sql.match(/INSERT INTO `Izin`[\s\S]*?VALUES\s*([\s\S]*?);/i);
  if (!m) return [];
  const rowRe = /\(([^()]+)\)/g;
  const rows = [];
  let r;
  while ((r = rowRe.exec(m[1])) !== null) {
    const values = [...r[1].matchAll(/'((?:[^'\\]|\\.)*)'/g)].map((v) => v[1]);
    if (values.length === 4) rows.push({ Id: values[0], Kode: values[1], Nama: values[2], Modul: values[3] });
  }
  return rows;
}

function topologicalSort(tables) {
  const byName = new Map(tables.map((t) => [t.name, t]));
  const graph = new Map(tables.map((t) => [t.name, new Set()]));
  const deferred = [];

  for (const t of tables) {
    for (const fk of t.foreignKeys) {
      if (fk.targetTable === t.name) continue;
      if (!byName.has(fk.targetTable)) continue;
      graph.get(t.name).add(fk.targetTable);
    }
  }

  const visited = new Set();
  const visiting = new Set();
  const order = [];

  function visit(name, chain) {
    if (visited.has(name)) return;
    if (visiting.has(name)) {
      const cycleAt = chain.indexOf(name);
      const cycleTable = chain[chain.length - 1];
      const dep = chain[cycleAt];
      graph.get(cycleTable).delete(dep);
      deferred.push({ table: cycleTable, dependsOn: dep });
      return;
    }
    visiting.add(name);
    for (const dep of graph.get(name) || []) {
      visit(dep, [...chain, name]);
    }
    visiting.delete(name);
    visited.add(name);
    order.push(name);
  }

  for (const t of tables) visit(t.name, []);

  return { order, deferred };
}

function toSnakeCase(pascal) {
  return pascal
    .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
    .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
    .toLowerCase();
}

function phpStringLiteral(value) {
  return "'" + String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
}

function columnDefinition(col) {
  const t = col.sqlType;
  let expr;

  if (t === 'CHAR') expr = `$table->char('${col.name}', ${col.length || 255})`;
  else if (t === 'VARCHAR') expr = `$table->string('${col.name}', ${col.length || 255})`;
  else if (t === 'TEXT') expr = `$table->text('${col.name}')`;
  else if (t === 'LONGTEXT') expr = `$table->longText('${col.name}')`;
  else if (t === 'DATE') expr = `$table->date('${col.name}')`;
  else if (t === 'DATETIME') expr = `$table->dateTime('${col.name}', ${col.length || 0})`;
  else if (t === 'JSON') expr = `$table->json('${col.name}')`;
  else if (t === 'DECIMAL' || t === 'NUMERIC') {
    const [precision, scale] = String(col.length || '10,2').split(',').map((s) => s.trim());
    expr = `$table->decimal('${col.name}', ${precision}, ${scale})`;
  } else if (t === 'TINYINT' && String(col.length || '').trim() === '1') {
    expr = `$table->boolean('${col.name}')`;
  } else if (t === 'TINYINT') {
    expr = col.unsigned ? `$table->unsignedTinyInteger('${col.name}')` : `$table->tinyInteger('${col.name}')`;
  } else if (t === 'SMALLINT') {
    expr = col.unsigned ? `$table->unsignedSmallInteger('${col.name}')` : `$table->smallInteger('${col.name}')`;
  } else if (t === 'MEDIUMINT') {
    expr = col.unsigned ? `$table->unsignedMediumInteger('${col.name}')` : `$table->mediumInteger('${col.name}')`;
  } else if (t === 'BIGINT') {
    expr = col.unsigned ? `$table->unsignedBigInteger('${col.name}')` : `$table->bigInteger('${col.name}')`;
  } else if (t === 'INT' || t === 'INTEGER') {
    expr = col.unsigned ? `$table->unsignedInteger('${col.name}')` : `$table->integer('${col.name}')`;
  } else if (t === 'FLOAT') {
    expr = `$table->float('${col.name}')`;
  } else if (t === 'DOUBLE') {
    expr = `$table->double('${col.name}')`;
  } else {
    throw new Error(`Tipe kolom belum dipetakan: ${t} pada ${col.name}`);
  }

  if (col.nullable) expr += '->nullable()';

  if (col.useCurrent) expr += '->useCurrent()';
  else if (col.defaultLiteral?.type === 'string') expr += `->default(${phpStringLiteral(col.defaultLiteral.value)})`;
  else if (col.defaultLiteral?.type === 'number') expr += `->default(${col.defaultLiteral.value})`;

  if (col.useCurrentOnUpdate) expr += '->useCurrentOnUpdate()';

  return `            ${expr};`;
}

function renderTableMigration(table, deferredFks) {
  const lines = [];
  for (const col of table.columns) {
    if (col.name === table.primaryKeys[0] && table.primaryKeys.length === 1) {
      lines.push(columnDefinition(col).replace(';', '->primary();'));
      continue;
    }
    lines.push(columnDefinition(col));
  }

  for (const uq of table.uniqueKeys) {
    const cols = uq.columns.map((c) => `'${c}'`).join(', ');
    lines.push(`            $table->unique([${cols}], '${uq.name}');`);
  }
  for (const ix of table.indexKeys) {
    const cols = ix.columns.map((c) => `'${c}'`).join(', ');
    lines.push(`            $table->index([${cols}], '${ix.name}');`);
  }

  const deferredColumns = new Set(deferredFks.map((d) => d.column));
  for (const fk of table.foreignKeys) {
    if (deferredColumns.has(fk.column) && fk.targetTable !== table.name) continue;
    lines.push(
      `            $table->foreign('${fk.column}')->references('${fk.targetColumn}')->on('${fk.targetTable}');`,
    );
  }

  const body = lines.join('\n');

  return `<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('${table.name}', function (Blueprint $table): void {
${body}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('${table.name}');
    }
};
`;
}

function renderDeferredForeignKeysMigration(deferred, tables) {
  const byName = new Map(tables.map((t) => [t.name, t]));
  const lines = [];
  for (const d of deferred) {
    const table = byName.get(d.table);
    const fk = table.foreignKeys.find((f) => f.targetTable === d.dependsOn);
    if (!fk) continue;
    lines.push(`        Schema::table('${d.table}', function (Blueprint $table): void {`);
    lines.push(
      `            $table->foreign('${fk.column}')->references('${fk.targetColumn}')->on('${fk.targetTable}');`,
    );
    lines.push('        });');
    lines.push('');
  }

  const downLines = deferred.map((d) => {
    const table = byName.get(d.table);
    const fk = table.foreignKeys.find((f) => f.targetTable === d.dependsOn);
    return `        Schema::table('${d.table}', fn (Blueprint $table) => $table->dropForeign(['${fk.column}']));`;
  });

  return `<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

/**
 * Foreign key yang tidak bisa didefinisikan inline saat CREATE TABLE karena
 * membentuk siklus antar tabel; ditambahkan setelah seluruh tabel domain ada.
 */
return new class extends Migration
{
    public function up(): void
    {
${lines.join('\n')}
    }

    public function down(): void
    {
${downLines.join('\n')}
    }
};
`;
}

function renderViewsMigration(views) {
  const upStatements = views
    .map((v) => `        DB::statement(<<<'SQL'\n${v.sql}\nSQL);`)
    .join('\n\n');
  const downStatements = views.map((v) => `        DB::statement('DROP VIEW IF EXISTS \`${v.name}\`');`).join('\n');

  return `<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

/**
 * View operasional MySQL. Tidak dijalankan di SQLite (dipakai untuk test)
 * karena sintaks CASE/TIMESTAMPDIFF di sini spesifik MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

${upStatements}
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

${downStatements}
    }
};
`;
}

function renderIzinSeeder(rows) {
  const items = rows
    .map(
      (r) =>
        `                ['Id' => ${phpStringLiteral(r.Id)}, 'Kode' => ${phpStringLiteral(r.Kode)}, 'Nama' => ${phpStringLiteral(r.Nama)}, 'Modul' => ${phpStringLiteral(r.Modul)}],`,
    )
    .join('\n');

  return `<?php

declare(strict_types=1);

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;

/**
 * Izin dasar platform Amanpoll (bukan data tenant). Idempotent lewat upsert.
 */
final class IzinSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('Izin')->upsert(
            [
${items}
            ],
            uniqueBy: ['Id'],
            update: ['Kode', 'Nama', 'Modul'],
        );
    }
}
`;
}

function main() {
  const sql = fs.readFileSync(SQL_PATH, 'utf8');
  const { tables, views } = parseSqlSchema(sql);
  const izin = parseIzinSeed(sql);

  console.log(`Tabel: ${tables.length}, View: ${views.length}, Baris seed Izin: ${izin.length}`);

  const { order, deferred } = topologicalSort(tables);
  if (deferred.length) {
    console.log('FK yang ditunda (siklus terdeteksi):', deferred);
  }

  const byName = new Map(tables.map((t) => [t.name, t]));
  const deferredByTable = new Map();
  for (const d of deferred) {
    if (!deferredByTable.has(d.table)) deferredByTable.set(d.table, []);
    const table = byName.get(d.table);
    const fk = table.foreignKeys.find((f) => f.targetTable === d.dependsOn);
    deferredByTable.get(d.table).push({ column: fk.column, targetTable: d.dependsOn });
  }

  let seq = 1;
  const baseDate = '2026_01_02';
  const pad = (n) => String(n).padStart(6, '0');

  for (const name of order) {
    const table = byName.get(name);
    const fileName = `${baseDate}_${pad(seq)}_buat_tabel_${toSnakeCase(name)}.php`;
    fs.writeFileSync(
      path.join(MIGRATIONS_DIR, fileName),
      renderTableMigration(table, deferredByTable.get(name) || []),
    );
    seq++;
  }

  if (deferred.length) {
    fs.writeFileSync(
      path.join(MIGRATIONS_DIR, `${baseDate}_${pad(seq)}_tambah_foreign_key_siklus.php`),
      renderDeferredForeignKeysMigration(deferred, tables),
    );
    seq++;
  }

  fs.writeFileSync(
    path.join(MIGRATIONS_DIR, `${baseDate}_${pad(seq)}_buat_view_operasional.php`),
    renderViewsMigration(views),
  );

  fs.mkdirSync(SEEDERS_DIR, { recursive: true });
  fs.writeFileSync(path.join(SEEDERS_DIR, 'IzinSeeder.php'), renderIzinSeeder(izin));

  console.log(`Selesai: ${order.length} migration tabel dibuat.`);
}

main();
