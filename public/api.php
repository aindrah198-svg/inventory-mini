<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
function reply(array $data, int $status = 200): never { http_response_code($status); echo json_encode($data); exit; }
function fail(string $message): never { reply(['error'=>$message],422); }
try {
 $path = getenv('INVENTORY_DB') ?: dirname(__DIR__).'/storage/inventory.sqlite';
 if (!is_dir(dirname($path))) mkdir(dirname($path),0700,true);
 $db = new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;');
 $db->exec('CREATE TABLE IF NOT EXISTS products(id INTEGER PRIMARY KEY,sku TEXT NOT NULL UNIQUE,name TEXT NOT NULL,unit TEXT NOT NULL,min_stock INTEGER NOT NULL CHECK(min_stock>=0),stock INTEGER NOT NULL DEFAULT 0 CHECK(stock>=0));
 CREATE TABLE IF NOT EXISTS movements(id INTEGER PRIMARY KEY,product_id INTEGER NOT NULL REFERENCES products(id),kind TEXT NOT NULL CHECK(kind IN (\'in\',\'out\')),qty INTEGER NOT NULL CHECK(qty>0),balance INTEGER NOT NULL CHECK(balance>=0),note TEXT NOT NULL,created_at TEXT NOT NULL);');
 $action=$_GET['action'] ?? 'state';
 if ($_SERVER['REQUEST_METHOD']==='GET' && $action==='state') {
  $products=$db->query('SELECT * FROM products ORDER BY name')->fetchAll();
  $moves=$db->query('SELECT m.*,p.name,p.sku,p.unit FROM movements m JOIN products p ON p.id=m.product_id ORDER BY m.id DESC')->fetchAll();
  reply(['products'=>$products,'movements'=>$moves,'csrf'=>$_SESSION['csrf']]);
 }
 if ($_SERVER['REQUEST_METHOD']!=='POST') reply(['error'=>'Metode tidak diizinkan.'],405);
 if (!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) reply(['error'=>'Sesi tidak valid. Muat ulang halaman.'],403);
 $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
 if (!is_array($data)) fail('Data tidak valid.');
 function field(array $data,string $key,int $max): string { $v=$data[$key] ?? ''; if(!is_string($v)||trim($v)===''||mb_strlen(trim($v))>$max) fail('Kolom '.$key.' wajib diisi dan maksimal '.$max.' karakter.'); return trim($v); }
 function integer(array $data,string $key,int $min): int { $v=$data[$key]??null; if(!is_int($v)||$v<$min||$v>1000000000) fail('Nilai '.$key.' tidak valid.'); return $v; }
 if($action==='product') {
  $sku=strtoupper(field($data,'sku',40)); $name=field($data,'name',100); $unit=field($data,'unit',20); $min=integer($data,'min_stock',0);
  $q=$db->prepare('INSERT INTO products(sku,name,unit,min_stock) VALUES(?,?,?,?)');
  try{$q->execute([$sku,$name,$unit,$min]);}catch(PDOException $e){if($e->getCode()==='23000')fail('SKU sudah digunakan.');throw $e;}
  reply(['ok'=>true],201);
 }
 if($action==='movement') {
  $id=integer($data,'product_id',1); $qty=integer($data,'qty',1); $kind=$data['kind']??'';
  if(!in_array($kind,['in','out'],true))fail('Jenis transaksi tidak valid.');
  $note=field($data,'note',200);
  $db->exec('BEGIN IMMEDIATE');
  try {
   $q=$db->prepare('SELECT stock FROM products WHERE id=?');$q->execute([$id]);$p=$q->fetch();
   if(!$p){$db->exec('ROLLBACK');fail('Barang tidak ditemukan.');}
   $balance=(int)$p['stock']+($kind==='in'?$qty:-$qty);
   if($balance<0){$db->exec('ROLLBACK');fail('Stok tidak cukup. Tersedia '.$p['stock'].'.');}
   if($balance>1000000000){$db->exec('ROLLBACK');fail('Batas stok terlampaui.');}
   $db->prepare('UPDATE products SET stock=? WHERE id=?')->execute([$balance,$id]);
   $db->prepare('INSERT INTO movements(product_id,kind,qty,balance,note,created_at) VALUES(?,?,?,?,?,?)')->execute([$id,$kind,$qty,$balance,$note,gmdate('Y-m-d\TH:i:s\Z')]);
   $db->exec('COMMIT');
  }catch(Throwable $e){try{$db->exec('ROLLBACK');}catch(Throwable){}throw $e;}
  reply(['ok'=>true,'balance'=>$balance],201);
 }
 if($action==='demo') {
  $db->exec('BEGIN IMMEDIATE');
  if((int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn()!==0){$db->exec('ROLLBACK');fail('Data contoh hanya bisa dimuat ke inventori kosong.');}
  $q=$db->prepare('INSERT INTO products(sku,name,unit,min_stock,stock) VALUES(?,?,?,?,?)');
  $m=$db->prepare('INSERT INTO movements(product_id,kind,qty,balance,note,created_at) VALUES(?,\'in\',?,?,?,?)');
  foreach([['KAT-001','Kain katun premium','meter',50,240],['BEN-002','Benang jahit hitam','roll',20,12],['KAN-003','Kancing putih 12 mm','pcs',100,450],['RES-004','Resleting 20 cm','pcs',30,0]] as $row){$q->execute($row);$id=(int)$db->lastInsertId();if($row[4]>0)$m->execute([$id,$row[4],$row[4],'Saldo awal - data contoh',gmdate('Y-m-d\TH:i:s\Z')]);}
  $db->exec('COMMIT');reply(['ok'=>true],201);
 }
 reply(['error'=>'Aksi tidak ditemukan.'],404);
}catch(JsonException){reply(['error'=>'JSON tidak valid.'],400);}catch(Throwable $e){error_log((string)$e);reply(['error'=>'Terjadi kesalahan server. Silakan coba kembali.'],500);}
