<?php
require_once __DIR__ . '/config.php';
requireAuth();
session_write_close();

$filename = DB_NAME . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
$output = fopen('php://temp/maxmemory:2097152', 'w+b');

if ($output === false) {
    http_response_code(500);
    exit('Unable to prepare the database backup.');
}

$write = static function (string $content) use ($output): void {
    if (fwrite($output, $content) !== strlen($content)) {
        throw new RuntimeException('Unable to write the database backup.');
    }
};

try {
    $db = getDB();
    $write("-- MJ Casting database backup\n");
    $write('-- Generated: ' . date(DATE_ATOM) . "\n\n");
    $write("SET FOREIGN_KEY_CHECKS=0;\n");
    $write("SET NAMES utf8mb4;\n\n");

    $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $create = $db->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);
        if (!$create || !isset($create[1])) {
            throw new RuntimeException('Unable to read table definition.');
        }

        $write('DROP TABLE IF EXISTS ' . $quotedTable . ";\n");
        $write($create[1] . ";\n\n");

        $rows = $db->query('SELECT * FROM ' . $quotedTable);
        $columns = $rows->columnCount();
        $columnNames = [];
        for ($i = 0; $i < $columns; $i++) {
            $columnNames[] = '`' . str_replace('`', '``', $rows->getColumnMeta($i)['name']) . '`';
        }

        while ($row = $rows->fetch(PDO::FETCH_NUM)) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $quotedValue = $db->quote((string) $value);
                    if ($quotedValue === false) {
                        throw new RuntimeException('Unable to encode a database value.');
                    }
                    $values[] = $quotedValue;
                }
            }
            $write('INSERT INTO ' . $quotedTable . ' (' . implode(', ', $columnNames) . ') VALUES (' . implode(', ', $values) . ");\n");
        }

        $write("\n");
    }

    $write("SET FOREIGN_KEY_CHECKS=1;\n");
    rewind($output);
} catch (Throwable $error) {
    error_log('Database backup failed: ' . $error->getMessage());
    fclose($output);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Database backup failed. Please check the server error log.');
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');
fpassthru($output);
fclose($output);
