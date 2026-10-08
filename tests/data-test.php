<?php
namespace {
/**
 * Suite DATA (jalur simpan, aturan validasi, perakit publik, pratinjau tersimpan) dengan Eloquent + SQLite + validator sungguhan:
 *
 *   php tests/data-test.php <bootstrap-lab.php> <support.php>
 *
 * Memakai model CMS tiruan di bawah (bentuknya mengikuti model Anda) dan kode kit dari folder ini. Tidak menyentuh aplikasi Anda.
 */
[$_, $labBootstrap, $labSupport] = $argv + [null, null, null];
if (!$labBootstrap || !$labSupport) { fwrite(STDERR, "Pemakaian: php tests/data-test.php <bootstrap-lab.php> <support.php>\n"); exit(2); }
$ROOT = dirname(__DIR__);
require $labBootstrap; require $labSupport;
}
namespace App\Models {
  class Page extends \Illuminate\Database\Eloquent\Model { protected $table='pages'; protected $guarded=[]; protected $casts=['title'=>'array','slug'=>'array','content'=>'array','meta_title'=>'array','meta_description'=>'array','published_at'=>'datetime']; }
  class Tag extends \Illuminate\Database\Eloquent\Model { protected $table='tags'; protected $guarded=[]; public $timestamps=false; protected $casts=['name'=>'array','slug'=>'array']; }
  class Category extends \Illuminate\Database\Eloquent\Model { protected $table='categories'; protected $guarded=[]; public $timestamps=false; protected $casts=['name'=>'array','slug'=>'array']; }
  class PlainTag extends \Illuminate\Database\Eloquent\Model { protected $table='plain_tags'; protected $guarded=[]; public $timestamps=false; }
  class Post extends \Illuminate\Database\Eloquent\Model { public function tags() { return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id'); } protected $table='posts'; protected $guarded=[]; protected $casts=['title'=>'array','slug'=>'array','content'=>'array','meta_title'=>'array','meta_description'=>'array','published_at'=>'datetime']; }
}
namespace {
require_once $ROOT . '/app/Editor/Options.php';
foreach (['Options','Field','BlockType','BlockRegistry','Modules'] as $f_) require_once "$ROOT/app/Editor/$f_.php";
App\Editor\Modules::all();
use App\Content\{ContentRules, ContentType as T, ContentWriter, Slug};
use App\Models\{Page, Post, Snippet, Tag, Category, PlainTag};
use App\Content\{ContentDocument, LocaleMap, TagResolver, Names};
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Schema;

$db = boot_database();
Schema::create('users', function ($t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
foreach (glob($ROOT . '/database/migrations/*media*.php') as $f) { (require $f)->up(); }
(require $ROOT . '/database/migrations/2026_10_07_000001_create_snippets_table.php')->up();
foreach (['pages','posts'] as $tb) Schema::create($tb, function ($t) use ($tb) { $t->id(); $t->json('title')->nullable(); $t->json('slug')->nullable(); $t->json('content')->nullable(); $t->json('meta_title')->nullable(); $t->json('meta_description')->nullable(); $t->string('status')->nullable(); $t->timestamp('published_at')->nullable(); if ($tb==='posts') { $t->unsignedBigInteger('category_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('featured_image')->nullable(); } $t->timestamps(); });
Schema::create('categories', function ($t) { $t->id(); $t->text('name'); $t->text('slug')->nullable(); });
Schema::create('plain_tags', function ($t) { $t->id(); $t->string('name'); $t->string('slug'); });
Schema::create('tags', function ($t) { $t->id(); $t->string('name'); $t->string('slug'); });
Schema::create('post_tags', function ($t) { $t->unsignedBigInteger('post_id'); $t->unsignedBigInteger('tag_id'); });
DB::table('categories')->insert([['id'=>1,'name' => '{"id":"Kesehatan","en":"Health"}'],['id'=>2,'name'=>'{"id":"Program","en":"Programs"}'],['id'=>3,'name'=>'"Berita"']]);
DB::table('users')->insert(['name' => 'Admin']);

// ---- validator sungguhan (Illuminate\Validation) dengan penerjemah mini
$dict = ['validation.required'=>'The :attribute field is required.','validation.string'=>'The :attribute must be a string.','validation.max.string'=>'The :attribute must not be greater than :max characters.','validation.regex'=>'The :attribute format is invalid.','validation.unique'=>'The :attribute has already been taken.','validation.in'=>'The selected :attribute is invalid.','validation.array'=>'The :attribute must be an array.','validation.integer'=>'The :attribute must be an integer.','validation.min.numeric'=>'The :attribute must be at least :min.','validation.exists'=>'The selected :attribute is invalid.'];
$translator = new class($dict) implements Illuminate\Contracts\Translation\Translator {
  public function __construct(private array $d) {}
  public function get($key, array $replace = [], $locale = null) { return $this->d[$key] ?? $key; }
  public function choice($key, $number, array $replace = [], $locale = null) { return $this->d[$key] ?? $key; }
  public function getLocale() { return 'id'; } public function setLocale($locale) {}
};
$factory = new Illuminate\Validation\Factory($translator, Illuminate\Container\Container::getInstance());
$factory->setPresenceVerifier(new Illuminate\Validation\DatabasePresenceVerifier($db->getDatabaseManager()));
$val = fn (array $data, T $type, $id = null) => $factory->make($data, ContentRules::for($type, ['id','en'], $id), [], ContentRules::attributes($type, ['id','en']));

$ok=0;$bad=0; function t($n,$c,$x=''){ global $ok,$bad; $c?$ok++:$bad++; echo ($c?'  PASS  ':'  FAIL  ').$n.(!$c&&$x!==''?"   [$x]":'')."\n"; }
$s = fn ($v) => is_string($v) ? $v : json_encode($v);

echo "\nSlug\n";
foreach (['Pelayanan Kesehatan Ibu & Anak!'=>'pelayanan-kesehatan-ibu-dan-anak','  Café Résumé '=>'cafe-resume','HIV/AIDS 2026'=>'hiv-aids-2026','---'=>'','' =>'','Ünïcödé'=>'unicode','Tentang   Kami'=>'tentang-kami','Mitra & Donatur'=>'mitra-dan-donatur'] as $in=>$want) t("Slug::make(".json_encode($in).") = '$want'", Slug::make($in)===$want, Slug::make($in));
t('isValid: "tentang-kami" ya; "Tentang" "a--b" "-a" "" tidak', Slug::isValid('tentang-kami') && !Slug::isValid('Tentang') && !Slug::isValid('a--b') && !Slug::isValid('-a') && !Slug::isValid(''));

echo "\nNama rute (awalan dipertahankan)\n";
foreach ([[T::Page,'v2.page.create','edit','v2.page.edit'],[T::Page,'page.create','edit','page.edit'],[T::Article,'v2.article.write','edit','v2.article.edit'],[T::Snippet,'v2.snippet.create','edit','v2.snippet.edit'],[T::Snippet,'admin.v2.snippet.edit','edit','admin.v2.snippet.edit'],[T::Page,'dashboard','edit','page.edit']] as [$ty,$cur,$act,$want]) t("$cur + $act -> $want", $ty->routeNameFor($cur,$act)===$want, $ty->routeNameFor($cur,$act));

echo "\nAturan: Halaman\n";
$good = ['titles'=>['id'=>'Tentang Kami','en'=>'About Us'],'slug'=>['id'=>'tentang-kami','en'=>'about-us'],'status'=>'offline','content'=>['blocks'=>[],'order'=>[]],'meta_title'=>['id'=>'','en'=>null],'meta_description'=>['id'=>null,'en'=>'']];
t('data lengkap lolos', $val($good,T::Page)->passes(), $s($val($good,T::Page)->errors()->all()));
$v=$val(['titles'=>['id'=>'','en'=>''],'slug'=>['id'=>'','en'=>''],'status'=>'offline','content'=>[]],T::Page);
t('judul dan slug kosong ditolak di KEDUA bahasa', $v->fails() && !array_diff(['titles.id','titles.en','slug.id','slug.en'],$v->errors()->keys()), $s($v->errors()->keys()));
t("pesan memakai nama ramah ('Judul (ID)', 'Slug (EN)')", str_contains($s($v->errors()->all()),'Judul (ID)') && str_contains($s($v->errors()->all()),'Slug (EN)'), $s($v->errors()->all()));
$v=$val(['slug'=>['id'=>'Bukan Slug!','en'=>'a--b']]+$good,T::Page); t('format slug salah ditolak (huruf besar, tanda seru, tanda hubung ganda)', $v->errors()->has('slug.id') && $v->errors()->has('slug.en'));
foreach (['draft','published',''] as $st) t("status halaman '$st' ditolak", $val(['status'=>$st]+$good,T::Page)->errors()->has('status'));
t("status 'online' diterima", $val(['status'=>'online']+$good,T::Page)->passes());
$v=$val(['meta_description'=>['id'=>str_repeat('x',501),'en'=>'']]+$good,T::Page); t('deskripsi SEO >500 karakter ditolak; kosong/null boleh', $v->errors()->has('meta_description.id') && !$v->errors()->has('meta_title.en'));

echo "\nAturan: keunikan slug per bahasa (kolom JSON)\n";
$existing = Page::create(['title'=>['id'=>'Beranda','en'=>'Home'],'slug'=>['id'=>'tentang-kami','en'=>'home'],'status'=>'online','content'=>[]]);
$v=$val($good,T::Page);
t("slug ID yang sudah dipakai halaman lain ditolak (unique pada slug->id)", $v->errors()->has('slug.id') && !$v->errors()->has('slug.en'), $s($v->errors()->all()));
t('mengedit halaman itu sendiri dengan slug yang sama LOLOS (diabaikan lewat id)', $val($good,T::Page,$existing->id)->passes(), $s($val($good,T::Page,$existing->id)->errors()->all()));
t("slug yang sama di BAHASA lain tidak bentrok (en 'tentang-kami' vs id 'tentang-kami')", $val(['slug'=>['id'=>'unik-id','en'=>'tentang-kami']]+$good,T::Page)->passes());
t("keunikan memeriksa tabel yang tepat: artikel boleh memakai slug yang dipakai halaman", ($p=$val($good+[],T::Article))->errors()->has('slug.id')===false);

echo "\nAturan: Artikel\n";
$art = $good + ['category_id'=>null];
t('artikel tanpa kategori ditolak', $val(['status'=>'draft','category_id'=>null]+$good,T::Article)->errors()->has('category_id'));
t('kategori yang tidak ada ditolak', $val(['status'=>'draft','category_id'=>99]+$good,T::Article)->errors()->has('category_id'));
t("artikel dengan kategori, tag, status 'draft' lolos", $val(['status'=>'draft','category_id'=>1,'tags'=>[1],'slug'=>['id'=>'artikel-baru','en'=>'new-article']]+$good,T::Article)->passes(), $s($val(['status'=>'draft','category_id'=>1,'tags'=>[1],'slug'=>['id'=>'artikel-baru','en'=>'new-article']]+$good,T::Article)->errors()->all()));
t("artikel menerima 'scheduled' (admin), menolak 'online'", $val(['status'=>'scheduled','category_id'=>1,'tags'=>[1],'slug'=>['id'=>'a-b','en'=>'a-b']]+$good,T::Article)->passes() && $val(['status'=>'online','category_id'=>1,'tags'=>[1]]+$good,T::Article)->errors()->has('status'));

echo "\nAturan: Snippet\n";
$sn = ['titles'=>['id'=>'Ajakan Donasi','en'=>''],'status'=>'offline','content'=>[],'key'=>'donasi','description'=>null,'sort_order'=>0];
t('snippet: hanya judul bahasa pertama wajib; tanpa slug/metadata', $val($sn,T::Snippet)->passes(), $s($val($sn,T::Snippet)->errors()->all()));
$snip = Snippet::create(['key'=>'donasi','title'=>['id'=>'Donasi','en'=>'Donate'],'status'=>'offline']);
t('key yang sudah dipakai ditolak', $val($sn,T::Snippet)->errors()->has('key'));
t('mengedit snippet itu sendiri dengan key yang sama lolos', $val($sn,T::Snippet,$snip->id)->passes());
foreach (['Tidak Valid','a--b','',str_repeat('a',81)] as $k) t("key '".substr($k,0,12)."' ditolak", $val(['key'=>$k]+$sn,T::Snippet)->errors()->has('key'));
t('urutan negatif ditolak', $val(['sort_order'=>-1]+$sn,T::Snippet)->errors()->has('sort_order'));
t('judul bahasa pertama kosong ditolak untuk snippet', $val(['titles'=>['id'=>'','en'=>'x']]+$sn,T::Snippet)->errors()->has('titles.id'));

echo "\nPenulis record\n";
$state = fn (array $o = []) => $o + ['titles'=>['id'=>'Tentang Kami','en'=>'About Us'],'slug'=>['id'=>'tentang-kami-2','en'=>'about-us-2'],'meta_title'=>['id'=>'JS','en'=>'MT'],'meta_description'=>['id'=>'JD','en'=>'MD'],'status'=>'offline','blocks'=>['a'=>['id'=>'a','type'=>'heading','data'=>['level'=>'h2']]],'order'=>['a','hantu'],'settings'=>[],'key'=>'','description'=>'','is_closing'=>false,'sort_order'=>0,'category_id'=>null,'user_id'=>1];
$pg = new Page; ContentWriter::fill($pg,T::Page,$state()); $pg->save(); $pg = Page::find($pg->id);
t('halaman baru tersimpan: judul, slug, metadata', $pg->title['id']==='Tentang Kami' && $pg->slug['en']==='about-us-2' && $pg->meta_description['id']==='JD');
t('isi dikemas lewat ContentDocument: ID hantu di urutan dibuang, pengaturan bawaan ditambahkan', $pg->content['order']===['a'] && $pg->content['settings']['toc_position']==='right', $s($pg->content));
t("status 'offline' -> published_at tetap kosong", $pg->published_at === null);
ContentWriter::fill($pg,T::Page,$state(['status'=>'online'])); $pg->save(); $first = Page::find($pg->id)->published_at;
t("status 'online' pertama kali -> published_at terisi", $first !== null);
usleep(1_200_000); ContentWriter::fill($pg,T::Page,$state(['status'=>'online','titles'=>['id'=>'Diubah','en'=>'Changed']])); $pg->save();
t('menyimpan ulang tidak menggeser published_at', Page::find($pg->id)->published_at->equalTo($first));
ContentWriter::fill($pg,T::Page,$state(['status'=>'offline'])); $pg->save(); t('kembali offline tidak menghapus published_at', Page::find($pg->id)->published_at !== null);

$ar = new Post; ContentWriter::fill($ar,T::Article,$state(['status'=>'draft','category_id'=>1,'user_id'=>1])); $ar->save();
t('artikel: category_id dan user_id (penulis) terisi', $ar->fresh()->category_id===1 && $ar->fresh()->user_id===1);
ContentWriter::fill($ar,T::Article,$state(['status'=>'draft','category_id'=>1,'user_id'=>7])); $ar->save();
t('artikel: penulis asli TIDAK tertimpa saat editor lain menyimpan', $ar->fresh()->user_id===1);
ContentWriter::fill($ar,T::Article,$state(['status'=>'published','category_id'=>1])); $ar->save();
t("artikel: published_at terisi saat 'published' (bukan 'online')", $ar->fresh()->published_at !== null);

$sp = new Snippet; ContentWriter::fill($sp,T::Snippet,$state(['key'=>'Hubungi Kami!','description'=>'','is_closing'=>true,'sort_order'=>3,'status'=>'online','user_id'=>1]));
t('snippet: slug/metadata/published_at TIDAK disentuh', !array_key_exists('slug',$sp->getAttributes()) && !array_key_exists('meta_title',$sp->getAttributes()) && !array_key_exists('published_at',$sp->getAttributes()));
$sp->save(); $sp=Snippet::find($sp->id);
t("snippet: key dinormalkan model, deskripsi kosong -> null, penutup & urutan tersimpan", $sp->key==='hubungi-kami' && $sp->description===null && $sp->is_closing===true && $sp->sort_order===3);
t('snippet: created_by sekali, updated_by selalu', $sp->created_by===1 && $sp->updated_by===1);

echo "\nPeran: status artikel\n";
$A = ['category_id'=>1,'tags'=>[1],'slug'=>['id'=>'peran-uji','en'=>'role-test'],'titles'=>['id'=>'Judul Peran Uji','en'=>'Role Test Title']] + $good;
$rv = fn (array $o, bool $can, ?string $cur = null) => $factory->make($o + $A, ContentRules::for(T::Article, ['id','en'], null, $can, $cur), [], ContentRules::attributes(T::Article, ['id','en']));
t('penulis biasa: pilihan = draft & review', T::Article->statusesFor(false) === ['draft','review']);
t('penulis biasa: status saat ini (published) tetap sah, tidak menambah yang lain', T::Article->statusesFor(false,'published') === ['draft','review','published']);
t('admin/editor: keenam status', count(T::Article->statusesFor(true)) === 6);
t('halaman & snippet tidak dibatasi peran', T::Page->statusesFor(false) === ['offline','online'] && T::Snippet->statusesFor(false) === ['offline','online']);
t("SERVER menolak penulis biasa yang memaksa status 'published'", $rv(['status'=>'published'], false)->errors()->has('status'));
t("penulis biasa boleh 'review'", $rv(['status'=>'review'], false)->passes(), $s($rv(['status'=>'review'], false)->errors()->all()));
t("admin boleh 'published'", $rv(['status'=>'published'], true)->passes());
t('penulis biasa menyimpan artikel yang sudah published TANPA mengubah status: lolos', $rv(['status'=>'published'], false, 'published')->passes());
t("...tetapi tidak bisa meloncat ke 'archived'", $rv(['status'=>'archived'], false, 'published')->errors()->has('status'));

echo "\nAturan: tag artikel\n";
t('tanpa tag ditolak', $rv(['status'=>'draft','tags'=>[]], true)->errors()->has('tags'));
t('tag kosong/spasi ditolak', $rv(['status'=>'draft','tags'=>['  ']], true)->errors()->has('tags.0'));
t('campuran ID & nama baru lolos', $rv(['status'=>'draft','tags'=>[1,'Malaria']], true)->passes());
t('nama tag > 60 karakter ditolak', $rv(['status'=>'draft','tags'=>[str_repeat('x',61)]], true)->errors()->has('tags.0'));
t('tag bertipe lain (desimal / larik) ditolak', $rv(['status'=>'draft','tags'=>[1.5]], true)->errors()->has('tags.0') && $rv(['status'=>'draft','tags'=>[[1]]], true)->errors()->has('tags.0'));
t("pesan tag memakai nama ramah 'Tag'", str_contains($s($rv(['status'=>'draft','tags'=>[]], true)->errors()->all()), 'Tag'));

echo "\nSlug unik TAHAN baris lama (slug polos, bukan JSON)\n";
DB::table('posts')->insert(['title'=>'Judul Lama','slug'=>'slug-polos-lama','content'=>'<p>x</p>','status'=>'draft']);
DB::table('posts')->insert(['title'=>'{}','slug'=>'{"id":"artikel-lama","en":"old-article"}','content'=>'{}','status'=>'draft']);
$boom = null; try { $vv = $val(['slug'=>['id'=>'artikel-lama','en'=>'old-article']]+$good, T::Article); $vv->passes(); } catch (Throwable $e) { $boom = $e->getMessage(); }
t('baris berisi slug polos TIDAK membuat pemeriksaan unik melempar galat', $boom === null, (string) $boom);
t('slug JSON yang sama di baris lain tetap terdeteksi bentrok (id dan en)', $vv->errors()->has('slug.id') && $vv->errors()->has('slug.en'), $s($vv->errors()->all()));
t('slug baru yang tidak dipakai lolos walau ada baris polos', $val(['slug'=>['id'=>'benar-benar-baru','en'=>'brand-new']]+$good, T::Article)->errors()->has('slug.id') === false);
t("pesan galat unik menyebut nama ramah ('Slug (ID)')", str_contains($s($vv->errors()->get('slug.id')), 'Slug (ID)'), $s($vv->errors()->get('slug.id')));

echo "\nTagResolver (kolom name/slug ber-cast array, seperti model Tag Anda)\n";
Tag::query()->delete();
DB::table('tags')->insert([
  ['id'=>1,'name'=>'{"id":"Imunisasi","en":"Immunization"}','slug'=>'{"id":"imunisasi","en":"immunization"}'],
  ['id'=>2,'name'=>'"Malaria"','slug'=>'"malaria"'],     // bentuk string JSON (dibuat oleh versi lama kit ini)
  ['id'=>3,'name'=>'Stunting','slug'=>'stunting'],        // teks polos
]);
$before = Tag::count();
$ids = TagResolver::resolve([1, 'Immunization', 'imunisasi', 'IMUNISASI ', 'malaria', 'STUNTING', 999, '', '   ', 'Ibu & Anak']);
$newTag = Tag::orderByDesc('id')->first();
t('semua bentuk tag lama dikenali: nama Inggris, slug, huruf besar, string JSON, teks polos -> tidak ada duplikat', Tag::count() === $before + 1 && count($ids) === 4 && array_slice($ids,0,3) === [1,2,3], $s($ids).' jumlah '.Tag::count());
t('ID palsu (999), kosong, dan spasi dibuang', !in_array(999,$ids,true));
t('tag baru ditulis PER BAHASA seperti tag Anda lainnya: name {"id","en"}', $newTag->getRawOriginal('name') === '{"id":"Ibu & Anak","en":"Ibu & Anak"}', $newTag->getRawOriginal('name'));
t("slug tag baru per bahasa dan benar ('ibu-dan-anak')", $newTag->getRawOriginal('slug') === '{"id":"ibu-dan-anak","en":"ibu-dan-anak"}', $newTag->getRawOriginal('slug'));
$again = TagResolver::resolve(['ibu & anak','Ibu dan Anak']);
t('menyebut nama/slug tag baru itu lagi memakai tag yang sama', $again === [$newTag->id] && Tag::count() === $before + 1, $s($again));
$n = TagResolver::resolve(['2026']); t('nama "2026" (string) menjadi tag BARU, bukan ID 2026', Tag::where('id','!=',0)->get()->contains(fn($tg)=>Names::of($tg->name)==='2026') && $n !== [2026]);
TagResolver::resolve(['Baru Tok'], ['id','en','fr']); t('bahasa aktif ikut: tag baru memuat id, en, fr', array_keys(json_decode(Tag::orderByDesc('id')->first()->getRawOriginal('name'),true)) === ['id','en','fr']);
$pt = TagResolver::resolve(['Tag Polos Baru'], ['id','en'], PlainTag::class);
t('model tanpa cast array: tag dibuat sebagai teks polos (bukan JSON)', PlainTag::find($pt[0])->getRawOriginal('name') === 'Tag Polos Baru' && PlainTag::find($pt[0])->slug === 'tag-polos-baru');
t('daftar kosong -> kosong', TagResolver::resolve([]) === []);

echo "\nNames (nama kategori/tag ber-cast array)\n";
t('peta bahasa: bahasa aktif lebih dulu', Names::of(['id'=>'Kesehatan','en'=>'Health'],'en') === 'Health');
t('bahasa aktif kosong -> cadangan id, lalu en', Names::of(['id'=>'Kesehatan','en'=>'Health'],'fr') === 'Kesehatan' && Names::of(['id'=>'','en'=>'Health'],'id') === 'Health');
t('string JSON dan teks polos', Names::of('"Berita"') === 'Berita' && Names::of('Berita') === 'Berita' && Names::of('{"id":"X"}') === 'X');
t('kosong/aneh -> teks kosong', Names::of(null) === '' && Names::of([]) === '' && Names::of(['id'=>'']) === '' && Names::of(5) === '');
echo "\nDropdown kategori: gejala 'hanya id' (Options::normalize)\n";
$norm = App\Editor\Options::normalize(['' => '— Pilih kategori —', 1 => ['id'=>'Kesehatan','en'=>'Health'], 2 => ['id'=>'Program'], 7 => ['label'=>'Tetap','icon'=>'x']]);
$lab = fn ($i) => $i['label'] ?? ($i['name'] ?? $i['value']);
t("nama berupa peta bahasa kini jadi label ('Kesehatan'), bukan id", $lab($norm[1]) === 'Kesehatan' && $lab($norm[2]) === 'Program' && $norm[1]['value'] === 1);
t('definisi opsi yang sebenarnya (label, icon, ...) tidak terganggu', $norm[3]['label'] === 'Tetap' && $norm[3]['icon'] === 'x' && $norm[3]['value'] === 7 && $lab($norm[0]) === '— Pilih kategori —');

echo "\nKode pemilih kategori & tag DI BUILDER (diambil langsung dari berkasnya)\n";
$src = file_get_contents($ROOT . '/resources/views/components/content/⚡builder.blade.php');
preg_match('/public function categoryOptions\(\): array\s*\{.*?\n  \}\n/s', $src, $m1) && preg_match('/public function tagOptions\(\): array\s*\{.*?\n  \}\n/s', $src, $m2) or die("metode tidak ditemukan\n");
eval('namespace { use App\Content\ContentType; use App\Content\Names; class Probe3 { public $contentType; ' . $m1[0] . $m2[0] . ' } }');
$pr = new Probe3; $pr->contentType = T::Article;
$cats = $pr->categoryOptions();
t("categoryOptions: id => NAMA (JSON per bahasa, string JSON) dalam bahasa aktif, urut abjad", $cats === [3=>'Berita',1=>'Kesehatan',2=>'Program'], $s($cats));
$tg = $pr->tagOptions();
t('tagOptions: [{id,name}] semua nama berupa teks (tidak ada "Array to string conversion")', count($tg) === Tag::count() && !array_filter($tg, fn($x)=>!is_string($x['name']) || $x['name']===''), $s($tg));
$pr->contentType = T::Page; t('bukan artikel -> daftar kosong (tidak membebani halaman lain)', $pr->categoryOptions() === [] && $pr->tagOptions() === []);

echo "\nJudul artikel unik per bahasa (posts.title punya indeks unik)\n";
DB::table('posts')->insert(['title'=>'{"id":"Judul Unik","en":"Unique Title"}','slug'=>'{"id":"unik-slug","en":"unique-slug"}','content'=>'{}','status'=>'draft']);
$upid = DB::table('posts')->where('slug','like','%unik-slug%')->value('id');
$B = ['category_id'=>1,'tags'=>[1],'status'=>'draft','slug'=>['id'=>'sl-baru','en'=>'sl-new']] + $good;
$vt = $val(['titles'=>['id'=>'Judul Unik','en'=>'Beda']]+$B, T::Article);
t('artikel baru dengan judul yang sudah dipakai ditolak (judul ID), BUKAN galat SQL', $vt->errors()->has('titles.id') && !$vt->errors()->has('titles.en'), $s($vt->errors()->all()));
t('judul yang sama di bahasa Inggris juga ditolak', $val(['titles'=>['id'=>'Lain','en'=>'Unique Title']]+$B, T::Article)->errors()->has('titles.en'));
t('mengedit artikel itu sendiri dengan judul yang sama lolos', $val(['titles'=>['id'=>'Judul Unik','en'=>'Unique Title']]+$B, T::Article, $upid)->errors()->has('titles.id') === false);
t('halaman TIDAK terkena aturan judul unik (hanya artikel)', $val(['titles'=>['id'=>'Judul Unik','en'=>'Unique Title']]+['slug'=>['id'=>'p-baru','en'=>'p-new']]+$good, T::Page)->errors()->has('titles.id') === false);

echo "\nPenulis: relasi dan gambar sampul\n";
$ar2 = new Post; ContentWriter::fill($ar2,T::Article,$state(['status'=>'draft','category_id'=>1,'user_id'=>1,'tags'=>[1,'Tag Baru Uji']])); $ar2->save();
t("artikel baru: featured_image bawaan 'default.webp' (kolom wajib)", $ar2->fresh()->featured_image === 'default.webp');
$tagIds = ContentWriter::syncRelations($ar2, T::Article, ['tags'=>[1,'Tag Baru Uji']]);
t('tag tersimpan di tabel pivot (ID lama + tag yang baru dibuat)', count($tagIds) === 2 && $ar2->fresh()->tags()->count() === 2);
ContentWriter::syncRelations($ar2, T::Article, ['tags'=>[1]]);
t('menyimpan ulang dengan satu tag melepas yang lain', $ar2->fresh()->tags()->pluck('tags.id')->all() === [1]);
$ar2->featured_image = 'cover-abc.webp'; ContentWriter::fill($ar2,T::Article,$state(['status'=>'draft','category_id'=>1])); t('gambar sampul yang sudah dipilih tidak ditimpa bawaan', $ar2->featured_image === 'cover-abc.webp');
t('halaman/snippet: syncRelations tidak melakukan apa pun (null)', ContentWriter::syncRelations(new Page, T::Page, []) === null);

echo "\nPERLINDUNGAN DATA: artikel lama (HTML mentah) dibuka lalu disimpan\n";
$legacyHtml = '<h2>Imunisasi</h2><p>Dasar <strong>lengkap</strong></p><img src="https://x.test/storage/articles/a.webp" class="rounded-lg">';
$pid = DB::table('posts')->insertGetId(['title'=>'Judul Lama','slug'=>'"slug-lama"','content'=>$legacyHtml,'status'=>'draft','category_id'=>1,'user_id'=>1]);
$old = Post::find($pid);
t('(bahaya yang dicegah) membaca lewat cast array menghasilkan null: isi akan HILANG bila dipakai', $old->content === null && $old->toArray()['content'] === null);
t('nilai mentah database tetap utuh', $old->getRawOriginal('content') === $legacyHtml);
$doc = ContentDocument::fromRaw($old->getRawOriginal('content'), ['id','en']);
t('dibaca dari nilai mentah: diimpor sebagai satu Paragraf, ditandai imported', $doc->imported && count($doc->order) === 1);
t('judul & slug lama terbaca dari nilai mentah (teks polos / string JSON)', LocaleMap::from($old->getRawOriginal('title'), ['id','en']) === ['id'=>'Judul Lama','en'=>''] && LocaleMap::from($old->getRawOriginal('slug'), ['id','en']) === ['id'=>'slug-lama','en'=>'']);
ContentWriter::fill($old, T::Article, $state(['status'=>'draft','category_id'=>1,'blocks'=>$doc->blocks,'order'=>$doc->order,'settings'=>$doc->settings,'titles'=>['id'=>'Judul Lama','en'=>'Old'],'slug'=>['id'=>'slug-lama','en'=>'old-slug']])); $old->save();
$again = ContentDocument::fromRaw(Post::find($pid)->getRawOriginal('content'), ['id','en']);
$blk = $again->blocks[$again->order[0]];
t('setelah disimpan: kini dokumen blok (bukan imported) dan HTML lama UTUH, termasuk gambar & kelas', !$again->imported && $blk['data']['text']['id'] === $legacyHtml, $s($blk['data']['text']['id'] ?? null));

if (!function_exists('config')) { function config($key = null, $default = null) { return $GLOBALS['__pubcfg'][$key] ?? $default; } }
if (!function_exists('auth')) { function auth() { return new class { public function check() { return $GLOBALS['__authed'] ?? true; } public function id() { return 1; } }; } }
if (!function_exists('abort_unless')) { function abort_unless($c, $code) { if (!$c) throw new RuntimeException("abort $code"); } }
$GLOBALS['__authed'] = true;
eval('namespace { class SearchHarness { use \App\Livewire\Traits\SearchesLinkTargets; public array $activeLocales = ["id","en"]; } }');
$sh = new SearchHarness;

echo "\nPencarian tujuan tautan (SearchesLinkTargets)\n";
DB::table('pages')->insert([
  ['title'=>'{"id":"Tentang Kami","en":"About Us"}','slug'=>'{"id":"tentang-kami","en":"about-us"}','status'=>'online','content'=>'{}'],
  ['title'=>'{"id":"Program Malaria","en":"Malaria Programme"}','slug'=>'{"id":"program-malaria","en":"malaria"}','status'=>'offline','content'=>'{}'],
  ['title'=>'{"id":"Diskon 50% _spesial_","en":"Sale"}','slug'=>'{"id":"diskon","en":"sale"}','status'=>'online','content'=>'{}'],
  ['title'=>'Judul Polos Tentang','slug'=>'plain','status'=>'online','content'=>'{}'],   // baris lama: teks biasa, bukan JSON
]);
for ($i = 1; $i <= 12; $i++) DB::table('pages')->insert(['title'=>json_encode(['id'=>"Berita $i",'en'=>"News $i"]),'slug'=>json_encode(['id'=>"berita-$i",'en'=>"news-$i"]),'status'=>'online','content'=>'{}']);
$labels = fn (array $r) => array_column($r, 'label');
$boom = null; try { $r = $sh->searchLinkTargets('page', 'tentang'); } catch (Throwable $e) { $boom = $e->getMessage(); }
t('baris lama berisi judul teks biasa TIDAK membuat pencarian melempar galat JSON', $boom === null, (string) $boom);
t("mencari 'tentang' (tanpa peduli huruf besar) menemukan halaman JSON", in_array('Tentang Kami', $labels($r), true));
t('hasil hanya berisi id, label, dan status (tidak ada isi/kolom lain)', array_keys($r[0]) === ['id','label','hint'] && is_int($r[0]['id']));
$r = $sh->searchLinkTargets('page', 'programme');
t("mencari kata bahasa Inggris ('programme') menemukannya; label tampil dalam bahasa aplikasi (id)", $labels($r) === ['Program Malaria'], $s($r));
t("status ikut dikembalikan ('online'/'offline') agar editor bisa memberi tanda", $sh->searchLinkTargets('page','malaria')[0]['hint'] === 'offline');
t("karakter LIKE diperlakukan LITERAL: '50%' hanya cocok satu baris", $labels($sh->searchLinkTargets('page','50%')) === ['Diskon 50% _spesial_'], $s($sh->searchLinkTargets('page','50%')));
t("'_spesial_' literal (garis bawah bukan wildcard)", count($sh->searchLinkTargets('page','_spesial_')) === 1 && $sh->searchLinkTargets('page','s_ecial') === []);
t("'%%' tidak menjadi 'cocokkan semua'", $sh->searchLinkTargets('page','%%') === []);
t('maksimal 8 hasil', count($sh->searchLinkTargets('page','berita')) === 8);
t('paling baru lebih dulu', $labels($sh->searchLinkTargets('page','berita'))[0] === 'Berita 12');
$pagesBefore = DB::table('pages')->count();
t("percobaan injeksi SQL tidak galat, tidak mengembalikan apa pun, dan tabel utuh", $sh->searchLinkTargets('page', "' OR 1=1 --") === [] && $sh->searchLinkTargets('page', "x'); DROP TABLE pages;--") === [] && DB::table('pages')->count() === $pagesBefore);
t('kata terlalu pendek (<2) / jenis tak dikenal / kosong -> larik kosong tanpa query', $sh->searchLinkTargets('page','t') === [] && $sh->searchLinkTargets('user','tentang') === [] && $sh->searchLinkTargets('page','   ') === [] && $sh->searchLinkTargets("page' --",'tentang') === []);
t('kata sangat panjang dipotong (80) dan tidak galat', is_array($sh->searchLinkTargets('page', str_repeat('a', 5000))));
DB::table('posts')->insert(['title'=>'{"id":"Kisah Nurhaida","en":"Nurhaida Story"}','slug'=>'{"id":"kisah","en":"story"}','content'=>'{}','status'=>'published']);
t("jenis 'article' mencari di tabel artikel, bukan halaman", $labels($sh->searchLinkTargets('article','nurhaida')) === ['Kisah Nurhaida'] && $sh->searchLinkTargets('article','malaria') === [] && $labels($sh->searchLinkTargets('page','nurhaida')) === []);
$GLOBALS['__authed'] = false; $blocked = false; try { $sh->searchLinkTargets('page','tentang'); } catch (RuntimeException $e) { $blocked = str_contains($e->getMessage(), '403'); }
t('pengguna belum masuk -> ditolak (403)', $blocked); $GLOBALS['__authed'] = true;

echo "\nBerkas dalam tombol tercatat di 'Digunakan Di' (media_id bersarang)\n";
$cols = array_column(Illuminate\Support\Facades\Schema::getColumns('media'), 'name');
$mid = DB::table('media')->insertGetId(array_filter(['path'=>'laporan/a.pdf','original_name'=>'a.pdf','name'=>'a.pdf','mime_type'=>'application/pdf','mime'=>'application/pdf','size'=>1000,'disk'=>'public','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')], fn ($v, $k) => in_array($k, $cols, true), ARRAY_FILTER_USE_BOTH));
$block = fn (?int $media) => ['b1' => ['id'=>'b1','type'=>'button-builder','data'=>\App\Content\Blocks\BlockSanitizer::buttonBuilder(['buttons'=>[['label'=>['id'=>'Unduh','en'=>'Download'],'link'=>['kind'=>'file','media_id'=>$media,'url'=>'a.pdf']]]])]];
$sn = Snippet::create(['key'=>'unduh-laporan','title'=>['id'=>'Unduh','en'=>'Download'],'status'=>'online','content'=>['blocks'=>$block($mid),'order'=>['b1'],'settings'=>[]]]);
t('tombol berkas: media dicatat sebagai dipakai oleh snippet', DB::table('media_usages')->where('media_id',$mid)->count() === 1);
$sn->update(['content'=>['blocks'=>$block(null),'order'=>['b1'],'settings'=>[]]]);
t('tombol diubah ke tautan lain (media_id null) -> pemakaian dihapus', DB::table('media_usages')->where('media_id',$mid)->count() === 0);

echo "\nsearchInternalPages (trait SearchesInternalPages, dipindahkan dari page-editor)\n";
eval('namespace { class InternalHarness { use \App\Livewire\Traits\SearchesInternalPages; } }');
$ih = new InternalHarness; $GLOBALS['__authed'] = true;
DB::table('pages')->delete(); DB::table('posts')->delete();
$mk = fn ($t, $sl, $when, $st = 'online') => ['title'=>$t,'slug'=>$sl,'status'=>$st,'content'=>'{}','created_at'=>$when,'updated_at'=>$when];
DB::table('pages')->insert([
  $mk('{"id":"Tentang Kami","en":"About Us"}','{"id":"tentang-kami","en":"about-us"}','2026-01-02 00:00:00'),
  $mk('{"id":"Tentang Program","en":"About Programs"}','{"id":"tentang-program","en":"about-programs"}','2026-01-03 00:00:00'),
  $mk('{"id":"Tentang Tanpa Slug","en":"No Slug"}','{}','2026-01-04 00:00:00'),
  $mk('Judul Polos Tentang','plain-slug','2026-01-05 00:00:00'),                  // baris lama: bukan JSON
  $mk('{"id":"Diskon 50% _off_","en":"Sale"}','{"id":"diskon","en":"sale"}','2026-01-06 00:00:00'),
]);
for ($i = 1; $i <= 7; $i++) { DB::table('pages')->insert($mk(json_encode(['id'=>"Berita $i",'en'=>"News $i"]), json_encode(['id'=>"berita-$i",'en'=>"news-$i"]), "2026-02-0$i 00:00:00")); DB::table('posts')->insert($mk(json_encode(['id'=>"Kabar $i berita",'en'=>"Story $i"]), json_encode(['id'=>"kabar-$i",'en'=>"story-$i"]), "2026-03-0$i 00:00:00", 'published')); }
DB::table('posts')->insert($mk('{"id":"Kisah Nurhaida","en":"Nurhaida Story"}','{"en":"nurhaida-story"}','2026-04-01 00:00:00','published'));
$sip = fn ($k) => $ih->searchInternalPages($k);
$boom = null; try { $r = $sip('tentang'); } catch (Throwable $e) { $boom = $e->getMessage(); }
t('baris lama (judul bukan JSON) tidak membuat pencarian melempar galat', $boom === null, (string) $boom);
t("bentuk keluaran SAMA dengan versi lama: daftar murni berisi hanya 'title' dan 'url'", array_is_list($r) && array_unique(array_map(fn ($x) => implode(',', array_keys($x)), $r)) === ['title,url']);
t("judul berawalan emoji 📄 dan URL semu internal://page/<slug>", in_array(['title'=>'📄 Tentang Kami','url'=>'internal://page/tentang-kami'], $r, true), $s($r));
t('halaman tanpa slug dilewati (versi lama mencetak "internal://page/" kosong)', !array_filter($r, fn ($x) => str_contains($x['title'], 'Tanpa Slug')) && !array_filter($r, fn ($x) => $x['url'] === 'internal://page/'));
t('terbaru lebih dulu', $r[0]['url'] === 'internal://page/tentang-program');
t("kata dalam bahasa Inggris ('programs') ditemukan; judul tampil dalam bahasa aplikasi", in_array('📄 Tentang Program', array_column($sip('programs'), 'title'), true));
t('tanpa peduli huruf besar ("TENTANG KAMI")', in_array('📄 Tentang Kami', array_column($sip('TENTANG KAMI'), 'title'), true));
$ar = $sip('nurhaida');
t("artikel: URL internal://article/<slug>; slug hanya ada di bahasa en tetap dipakai (versi lama mencetak 'Array')", $ar === [['title'=>'📝 Kisah Nurhaida','url'=>'internal://article/nurhaida-story']], $s($ar));
t('maksimal 5 halaman + 5 artikel (10)', count($sip('berita')) === 10 && count(array_filter($sip('berita'), fn ($x) => str_starts_with($x['url'], 'internal://page/'))) === 5);
t('halaman lebih dulu, lalu artikel', ($b = array_column($sip('berita'), 'url'))[0] === 'internal://page/berita-7' && str_starts_with($b[5], 'internal://article/'));
t("'50%' dan '_off_' literal; '%%' tidak cocok semuanya", count($sip('50%')) === 1 && count($sip('_off_')) === 1 && $sip('o_f') === [] && $sip('%%') === []);
t("injeksi SQL tidak galat dan tidak mengembalikan apa pun", $sip("' OR 1=1 --") === [] && $sip("x'); DROP TABLE pages;--") === [] && DB::table('pages')->count() === 12);
t('kosong / spasi / null -> larik kosong', $sip('') === [] && $sip('   ') === [] && $sip(null) === []);
t('kata sangat panjang dipotong dan tidak galat', is_array($sip(str_repeat('b', 9000))));
$GLOBALS['__authed'] = false; $blk = false; try { $sip('tentang'); } catch (RuntimeException $e) { $blk = str_contains($e->getMessage(), '403'); } $GLOBALS['__authed'] = true;
t('pengguna belum masuk ditolak (403)', $blk);

echo "\nBlok modul di jalur simpan dan pratinjau (Akordion/FAQ)\n";
$evilFaq = ['style'=>'neon" onload="1','color'=>['x'],'schema'=>'1','items'=>array_merge(
    [['id'=>'a b','question'=>['id'=>"  Usia <5\ttahun?\n",'en'=>'x','fr'=>'bocor'],'answer'=>['id'=>"Satu\r\n\r\n\r\n\r\nDua <b>x</b>\x07",'en'=>'']]],
    array_fill(0, 45, ['question'=>['id'=>'Q'],'answer'=>['id'=>'A']]))];
$pgF = new Page;
ContentWriter::fill($pgF, T::Page, $state(['status'=>'offline','slug'=>['id'=>'faq-uji','en'=>'faq-test'],'titles'=>['id'=>'FAQ Uji','en'=>'FAQ Test'],'blocks'=>['f1'=>['id'=>'f1','type'=>'accordion-builder','data'=>$evilFaq],'h'=>['id'=>'h','type'=>'heading','data'=>['text'=>['id'=>'<b>x</b>']]]],'order'=>['f1','h'],'locales'=>['id','en']]));
$pgF->save(); $stored = Page::find($pgF->id)->content['blocks'];
t('penulis record membersihkan blok modul: gaya/warna sisipan -> bawaan, bendera boolean', $stored['f1']['data']['style']==='boxed' && $stored['f1']['data']['color']==='foresty' && $stored['f1']['data']['schema']===true);
t('...dibatasi 30 pertanyaan, bahasa di luar daftar dibuang, ID tak sah diganti', count($stored['f1']['data']['items'])===30 && array_keys($stored['f1']['data']['items'][0]['question'])===['id','en'] && preg_match('/^itm_[a-f0-9]{8}$/', $stored['f1']['data']['items'][0]['id']));
t('...teks medis "usia <5" UTUH, tab/baris baru diringkas di pertanyaan, baris baru jawaban terjaga (maks. dua), karakter kontrol hilang', $stored['f1']['data']['items'][0]['question']['id']==='Usia <5 tahun?' && $stored['f1']['data']['items'][0]['answer']['id']==="Satu\n\nDua <b>x</b>", json_encode($stored['f1']['data']['items'][0]));
t('blok lain (judul) TIDAK disentuh', $stored['h']['data']==['text'=>['id'=>'<b>x</b>']]);
$reload = Page::find($pgF->id)->content['blocks']['f1']['data'];
t('data yang sudah bersih lolos pembersih lagi tanpa berubah (idempoten lewat database)', \App\Editor\Blocks\AccordionBlock::sanitize($reload, ['id','en']) === $reload);
$ps = new App\Content\PreviewStore(new class { public array $d=[]; function get($k){return $this->d[$k]??null;} function put($k,$v,$t){$this->d[$k]=$v;} function forget($k){unset($this->d[$k]);} });
$tok = $ps->publish('page', 1, null, ['f1'=>['id'=>'f1','type'=>'accordion-builder','data'=>$evilFaq]], ['f1'], [], ['id','en']);
$pv = $ps->get($tok['token'], 1);
t('titipan pratinjau juga membersihkan blok modul (kanvas tidak merender data yang tak lolos simpan)', count($pv['blocks']['f1']['data']['items'])===30 && $pv['blocks']['f1']['data']['style']==='boxed');

echo "\nDaftar unduhan dengan model Media sungguhan\n";
Illuminate\Container\Container::getInstance()->instance('filesystem', new class { function disk($n = null) { return new class { function url($p) { return "https://x.test/storage/$p"; } }; } });
$mk = fn (string $path, string $name, string $mime, int $size) => DB::table('media')->insertGetId(['disk'=>'public','path'=>$path,'original_name'=>$name,'mime_type'=>$mime,'size'=>$size,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
$m1 = $mk('laporan/lap-2025.pdf','lap-2025.pdf','application/pdf',1258291); $m2 = $mk('laporan/lap-2024.docx','lap-2024.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document',870400); $m3 = $mk('laporan/audit.xlsx','audit.xlsx','application/vnd.ms-excel',2048);
$info = App\Content\Blocks\FileInfo::lookup([$m1, $m2, $m3, $m1, 0, -4, 'x', 99999]);
t('lookup: tiga berkas ditemukan; ID ganda/nol/negatif/bukan angka/tak ada diabaikan', array_keys($info) === [$m1, $m2, $m3], json_encode(array_keys($info)));
t('lookup: url dari Media::url(), nama asli, mime, ukuran', $info[$m1] === ['url'=>'https://x.test/storage/laporan/lap-2025.pdf','name'=>'lap-2025.pdf','mime'=>'application/pdf','size'=>1258291]);
t('lookup: daftar kosong / semua tak sah -> [] tanpa query', App\Content\Blocks\FileInfo::lookup([]) === [] && App\Content\Blocks\FileInfo::lookup([0, -1]) === []);
$dataD = App\Editor\Blocks\DownloadsBlock::sanitize(['group_by'=>'year','sort'=>'year_desc','items'=>[
    ['title'=>['id'=>'Laporan 2025'],'year'=>'2025','file'=>['media_id'=>$m1]], ['title'=>['id'=>'Laporan 2024'],'year'=>'2024','file'=>['media_id'=>$m2]], ['title'=>['id'=>'Audit'],'file'=>['media_id'=>$m3]]]], ['id','en']);
$ids = array_map(fn ($i) => $i['file']['media_id'], $dataD['items']);
$list = App\Content\Blocks\DownloadList::prepare($dataD, App\Content\Blocks\FileInfo::lookup($ids), 'id');
t('ujung ke ujung: judul kelompok tahun menurun, ukuran dan jenis dari Media', array_column($list['groups'], 'heading') === ['2025','2024','Tanpa tahun'] && $list['groups'][0]['items'][0]['size'] === '1,2 MB' && $list['groups'][1]['items'][0]['type'] === 'Word' && $list['groups'][2]['items'][0]['type'] === 'Excel');
App\Models\Media::find($m2)->delete();   // soft delete
$after = App\Content\Blocks\DownloadList::prepare($dataD, App\Content\Blocks\FileInfo::lookup($ids), 'id');
t('berkas yang DIHAPUS (soft delete) tidak lagi ditemukan; butirnya dilewati di situs, tetap tampak (bertanda) di kanvas', !isset(App\Content\Blocks\FileInfo::lookup($ids)[$m2]) && $after['count'] === 2 && App\Content\Blocks\DownloadList::prepare($dataD, App\Content\Blocks\FileInfo::lookup($ids), 'id', true)['count'] === 3);
$blockD = fn (array $mediaIds) => ['d' => ['id'=>'d','type'=>'downloads-builder','data'=>App\Editor\Blocks\DownloadsBlock::sanitize(['items'=>array_map(fn ($m) => ['title'=>['id'=>"B$m"],'file'=>['media_id'=>$m,'url'=>'x.pdf']], $mediaIds)], ['id','en'])]];
$snD = Snippet::create(['key'=>'unduhan-uji','title'=>['id'=>'Unduhan','en'=>'Downloads'],'status'=>'online','content'=>['blocks'=>$blockD([$m1, $m3]),'order'=>['d'],'settings'=>[]]]);
$usage = fn (int $m) => DB::table('media_usages')->where('media_id', $m)->count();
t("berkas di dalam butir daftar tercatat di 'Digunakan Di' (media_id bersarang items.N.file)", $usage($m1) === 1 && $usage($m3) === 1);
$snD->update(['content'=>['blocks'=>$blockD([$m1]),'order'=>['d'],'settings'=>[]]]);
t('butir dibuang -> pencatatan berkas itu dihapus; yang tersisa tetap', $usage($m3) === 0 && $usage($m1) === 1);

echo "\nPratinjau versi TERSIMPAN (SavedPreview)\n";
// bagian ini menguji isi record SENDIRI: nonaktifkan snippet penutup yang dibuat langkah sebelumnya, pulihkan di akhir bagian
$closingIds = DB::table('snippets')->where('is_closing',1)->pluck('id')->all(); DB::table('snippets')->whereIn('id',$closingIds)->update(['is_closing'=>0]);
$SP = fn (App\Content\ContentType $ct, $rec, string $lang = 'id') => App\Content\SavedPreview::from($ct, $rec, ['id','en'], $lang);
$evilBlocks = ['b'=>['id'=>'b','type'=>'button-builder','data'=>['buttons'=>[['label'=>['id'=>'X'],'link'=>['kind'=>'url','ref'=>'javascript:alert(1)']]]]],'h'=>['id'=>'h','type'=>'heading','data'=>['text'=>['id'=>'Judul']]]];
$pgS = Page::create(['title'=>['id'=>'Tentang Kami','en'=>'About Us'],'slug'=>['id'=>'tentang-kami-s','en'=>'about-us-s'],'status'=>'online','content'=>['blocks'=>$evilBlocks,'order'=>['b','hantu','h'],'settings'=>[]]]);
$r = $SP(App\Content\ContentType::Page, Page::find($pgS->id));
t('halaman: jenis, judul sesuai bahasa, status, dan ditandai terbit (online)', $r['type']==='page' && $r['title']==='Tentang Kami' && $r['status']==='online' && $r['published']===true && $SP(App\Content\ContentType::Page, Page::find($pgS->id), 'en')['title']==='About Us');
t('halaman: data DIBERSIHKAN (tautan javascript: dikosongkan), ID hantu dibuang, pengaturan bawaan terisi', $r['blocks']['b']['data']['buttons'][0]['link']['ref']==='' && $r['order']===['b','h'] && ($r['settings']['toc_position'] ?? null)==='right' && $r['imported']===false);
DB::table('pages')->where('id',$pgS->id)->update(['status'=>'offline']);
t('halaman OFFLINE tetap bisa dipratinjau, ditandai belum terbit', $SP(App\Content\ContentType::Page, Page::find($pgS->id))['published']===false && $SP(App\Content\ContentType::Page, Page::find($pgS->id))['status']==='offline');
$legacyId = DB::table('posts')->insertGetId(['title'=>'Judul Artikel Lama','slug'=>'"slug-lama-s"','content'=>'<h2>Imunisasi</h2><p>Dasar <strong>lengkap</strong></p>','status'=>'published','category_id'=>1,'user_id'=>1]);
$legacy = Post::find($legacyId);
t('(bahaya yang dicegah) artikel lama: cast array menghasilkan null, tetapi pratinjau membaca NILAI MENTAH', $legacy->content === null);
$ra = $SP(App\Content\ContentType::Article, $legacy);
t("artikel lama: judul teks polos terbaca; HTML diimpor sebagai satu Paragraf utuh; ditandai imported; status 'published' = terbit", $ra['title']==='Judul Artikel Lama' && $ra['imported']===true && count($ra['order'])===1 && $ra['blocks'][$ra['order'][0]]['data']['text']['id']==='<h2>Imunisasi</h2><p>Dasar <strong>lengkap</strong></p>' && $ra['published']===true);
DB::table('posts')->where('id',$legacyId)->update(['status'=>'draft']);
t("artikel 'draft' ditandai belum terbit (bukan 'online': artikel memakai 'published')", $SP(App\Content\ContentType::Article, Post::find($legacyId))['published']===false);
$spId = DB::table('snippets')->insertGetId(['key'=>'cta-preview','title'=>'{"id":"CTA Donasi","en":"Donate CTA"}','content'=>json_encode(['blocks'=>['h'=>['id'=>'h','type'=>'heading','data'=>[]]],'order'=>['h'],'settings'=>[]]),'status'=>'online','is_closing'=>0,'sort_order'=>0,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
$rs = $SP(App\Content\ContentType::Snippet, Snippet::find($spId));
t('snippet: judul, status online = terbit, blok terbaca', $rs['title']==='CTA Donasi' && $rs['published']===true && $rs['order']===['h']);
$emptyId = DB::table('pages')->insertGetId(['title'=>'','slug'=>'{}','status'=>'offline','content'=>'null','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
$re = $SP(App\Content\ContentType::Page, Page::find($emptyId));
t("record tanpa judul dan isi: tidak galat; judul cadangan '#id'; tanpa blok", $re['title']==='#'.$emptyId && $re['blocks']===[] && $re['order']===[]);
DB::table('snippets')->whereIn('id',$closingIds)->update(['is_closing'=>1]);   // pulihkan
echo "\nCallout di jalur simpan, pratinjau kanvas, dan pratinjau tersimpan\n";
$evilCo = ['tone'=>'neon" onload="1','style'=>'x','show_icon'=>'','icon'=>'a b','title'=>['id'=>"  Usia <5\ttahun\n",'fr'=>'bocor'],'body'=>['id'=>"Satu\r\n\r\n\r\n\r\nDua\x07"],
           'action'=>['label'=>['id'=>'Hubungi 119'],'link'=>['kind'=>'url','ref'=>'javascript:alert(1)','new_tab'=>'1']]];
$pgC = new Page;
ContentWriter::fill($pgC, T::Page, $state(['status'=>'offline','slug'=>['id'=>'co-uji','en'=>'co-test'],'titles'=>['id'=>'CO Uji','en'=>'CO Test'],'blocks'=>['c1'=>['id'=>'c1','type'=>'callout-builder','data'=>$evilCo]],'order'=>['c1'],'locales'=>['id','en']]));
$pgC->save(); $stC = Page::find($pgC->id)->content['blocks']['c1']['data'];
t('penulis record membersihkan Callout: jenis/gaya/ikon tak sah -> bawaan; tautan javascript: dikosongkan; teks medis "<5" utuh', $stC['tone']==='info' && $stC['style']==='soft' && $stC['icon']==='' && $stC['show_icon']===false && $stC['action']['link']['ref']==='' && $stC['title']['id']==='Usia <5 tahun' && $stC['body']['id']==="Satu\n\nDua", json_encode($stC));
t('data bersih lolos pembersih lagi tanpa berubah lewat database (idempoten)', \App\Editor\Blocks\CalloutBlock::sanitize($stC, ['id','en']) === $stC);
$tokC = $ps->publish('page', 1, null, ['c1'=>['id'=>'c1','type'=>'callout-builder','data'=>$evilCo]], ['c1'], [], ['id','en']);
t('titipan pratinjau kanvas juga membersihkan Callout', $ps->get($tokC['token'],1)['blocks']['c1']['data']['action']['link']['ref']==='' && $ps->get($tokC['token'],1)['blocks']['c1']['data']['tone']==='info');
$svC = App\Content\SavedPreview::from(T::Page, Page::find($pgC->id), ['id','en'], 'id');
t('pratinjau tersimpan membaca Callout dari database dan membersihkannya', $svC['blocks']['c1']['data']['tone']==='info' && $svC['blocks']['c1']['data']['action']['link']['ref']==='');

echo "\nVideo di jalur simpan, pencatatan media, dan pratinjau\n";
$evilV = ['url'=>"javascript:alert(1)\x00\n",'title'=>['id'=>"  Usia <5\ttahun\n",'fr'=>'bocor'],'caption'=>['id'=>str_repeat('c',400)],'poster'=>['media_id'=>$m1,'url'=>'https://x.test/storage/laporan/lap-2025.pdf'],'ratio'=>'99:1" onload="1','max_width'=>['x']];
$pgV = new Page;
ContentWriter::fill($pgV, T::Page, $state(['status'=>'offline','slug'=>['id'=>'vid-uji','en'=>'vid-test'],'titles'=>['id'=>'Vid Uji','en'=>'Vid Test'],'blocks'=>['v1'=>['id'=>'v1','type'=>'video-builder','data'=>$evilV]],'order'=>['v1'],'locales'=>['id','en']]));
$pgV->save(); $stV = Page::find($pgV->id)->content['blocks']['v1']['data'];
t('penulis record membersihkan Video: rasio/lebar tak sah -> bawaan; judul "<5" utuh; keterangan dipotong 300; karakter kontrol dibuang', $stV['ratio']==='16:9' && $stV['max_width']==='full' && $stV['title']==['id'=>'Usia <5 tahun','en'=>''] && mb_strlen($stV['caption']['id'])===300 && !preg_match('/[\x00-\x1f]/', $stV['url']), json_encode($stV));
t('data bersih lolos pembersih lagi tanpa berubah lewat database (idempoten)', \App\Editor\Blocks\VideoBlock::sanitize($stV, ['id','en']) === $stV);
t('alamat tak sah tersimpan apa adanya sebagai TEKS tetapi TIDAK PERNAH menjadi alamat yang dipasang (VideoUrl menolaknya)', $stV['url']==='javascript:alert(1)' && App\Content\Blocks\VideoUrl::parse($stV['url'])===null);
$vidSn = fn (?int $poster) => ['v'=>['id'=>'v','type'=>'video-builder','data'=>App\Editor\Blocks\VideoBlock::sanitize(['url'=>'https://youtu.be/dQw4w9WgXcQ','poster'=>['media_id'=>$poster,'url'=>'sampul.jpg']], ['id','en'])]];
$usageV = fn (int $m) => DB::table('media_usages')->where('media_id', $m)->count();
t('(awal) berkas sampul belum tercatat di mana pun', $usageV($m3) === 0);
$snV = Snippet::create(['key'=>'video-uji','title'=>['id'=>'Video','en'=>'Video'],'status'=>'online','content'=>['blocks'=>$vidSn($m3),'order'=>['v'],'settings'=>[]]]);
t("gambar sampul tercatat di 'Digunakan Di' lewat media_id (poster bersarang di data.poster)", $usageV($m3) === 1, (string) $usageV($m3));
$snV->update(['content'=>['blocks'=>$vidSn(null),'order'=>['v'],'settings'=>[]]]);
t('sampul dilepas -> pencatatan hilang (dari 1 menjadi 0)', $usageV($m3) === 0, (string) $usageV($m3));
$tokV = $ps->publish('page', 1, null, ['v1'=>['id'=>'v1','type'=>'video-builder','data'=>$evilV]], ['v1'], [], ['id','en']);
t('titipan pratinjau kanvas membersihkan Video', $ps->get($tokV['token'],1)['blocks']['v1']['data']['ratio']==='16:9');
$svV = App\Content\SavedPreview::from(T::Page, Page::find($pgV->id), ['id','en'], 'id');
t('pratinjau tersimpan membaca Video dari database dan membersihkannya', $svV['blocks']['v1']['data']['ratio']==='16:9' && $svV['blocks']['v1']['data']['title']['id']==='Usia <5 tahun');

echo "\nGaleri di jalur simpan, pencatatan media, dan pratinjau\n";
$evilG = ['mode'=>'x" onload="1','layout'=>['x'],'columns'=>99,'ratio'=>'9:16','grayscale'=>'','lightbox'=>'0','autoplay'=>'ya','items'=>[
    ['id'=>'a b','title'=>['id'=>"  Usia <5\ttahun\n",'fr'=>'bocor'],'caption'=>['id'=>str_repeat('k',400)],'image'=>['media_id'=>$m1,'url'=>"javascript:alert(1)\x00"],'link'=>['kind'=>'url','ref'=>'javascript:alert(1)','new_tab'=>'1']],
    ['image'=>['media_id'=>'x','url'=>'https://evil.test/a.png'],'link'=>['kind'=>'tel','ref'=>'119','new_tab'=>true]], 'bukan-larik']];
$pgG = new Page;
ContentWriter::fill($pgG, T::Page, $state(['status'=>'offline','slug'=>['id'=>'gal-uji','en'=>'gal-test'],'titles'=>['id'=>'Gal Uji','en'=>'Gal Test'],'blocks'=>['g1'=>['id'=>'g1','type'=>'gallery-builder','data'=>$evilG]],'order'=>['g1'],'locales'=>['id','en']]));
$pgG->save(); $stG = Page::find($pgG->id)->content['blocks']['g1']['data'];
t('penulis record membersihkan Galeri: mode/tampilan/kolom/rasio tak sah -> bawaan; kolom disimpan sebagai TEKS; bendera boolean', $stG['mode']==='photos' && $stG['layout']==='grid' && $stG['columns']==='3' && $stG['ratio']==='4:3' && $stG['grayscale']===false && $stG['lightbox']===false && $stG['autoplay']===true, json_encode($stG));
t('butir: entri bukan larik dibuang; judul "<5" utuh; url tanpa media_id dikosongkan; tautan javascript: dikosongkan; telepon 119 sah tanpa tab baru', count($stG['items'])===2 && $stG['items'][0]['title']['id']==='Usia <5 tahun' && $stG['items'][1]['image']===['media_id'=>null,'url'=>''] && $stG['items'][0]['link']['ref']==='' && $stG['items'][1]['link']['ref']==='119' && $stG['items'][1]['link']['new_tab']===false, json_encode($stG['items']));
t('data bersih lolos pembersih lagi tanpa berubah lewat database (idempoten)', \App\Editor\Blocks\GalleryBlock::sanitize($stG, ['id','en']) === $stG);
$galSn = fn (array $ids) => ['g'=>['id'=>'g','type'=>'gallery-builder','data'=>\App\Editor\Blocks\GalleryBlock::sanitize(['items'=>array_map(fn ($m) => ['title'=>['id'=>"F$m"],'image'=>['media_id'=>$m,'url'=>'x.jpg']], $ids)], ['id','en'])]];
$uG = fn (int $m) => DB::table('media_usages')->where('media_id', $m)->count();
$b1 = $uG($m1); $b3 = $uG($m3);
$snG = Snippet::create(['key'=>'galeri-uji','title'=>['id'=>'Galeri','en'=>'Gallery'],'status'=>'online','content'=>['blocks'=>$galSn([$m3,$m1]),'order'=>['g'],'settings'=>[]]]);
t("kedua gambar di dalam butir tercatat di 'Digunakan Di' (items.N.image.media_id bersarang): masing-masing bertambah 1", $uG($m3)===$b3+1 && $uG($m1)===$b1+1, "$b1/$b3 -> ".$uG($m1).'/'.$uG($m3));
$snG->update(['content'=>['blocks'=>$galSn([$m1]),'order'=>['g'],'settings'=>[]]]);
t('satu butir dibuang -> pencatatannya hilang (kembali ke angka awal); yang tersisa tetap tercatat', $uG($m3)===$b3 && $uG($m1)===$b1+1, "$b1/$b3 -> ".$uG($m1).'/'.$uG($m3));
$tokG = $ps->publish('page', 1, null, ['g1'=>['id'=>'g1','type'=>'gallery-builder','data'=>$evilG]], ['g1'], [], ['id','en']);
t('titipan pratinjau kanvas membersihkan Galeri', $ps->get($tokG['token'],1)['blocks']['g1']['data']['columns']==='3' && $ps->get($tokG['token'],1)['blocks']['g1']['data']['items'][0]['link']['ref']==='');
$svG = App\Content\SavedPreview::from(T::Page, Page::find($pgG->id), ['id','en'], 'id');
t('pratinjau tersimpan membaca Galeri dari database dan membersihkannya', $svG['blocks']['g1']['data']['mode']==='photos' && count($svG['blocks']['g1']['data']['items'])===2);
$gA = $mk('galeri/a.jpg','a.jpg','image/jpeg',100); $gB = $mk('galeri/b.png','logo-mitra_b.png','image/png',100);
App\Models\Media::find($gB)->delete();   // soft delete
$e2e = \App\Editor\Blocks\GalleryBlock::sanitize(['items'=>[['title'=>['id'=>'A'],'image'=>['media_id'=>$gA]], ['title'=>['id'=>'B'],'image'=>['media_id'=>$gB]], ['title'=>['id'=>'PDF'],'image'=>['media_id'=>$m1]]]], ['id','en']);
$filesE = App\Content\Blocks\FileInfo::lookup([$gA,$gB,$m1]); $rsv = new App\Content\Links\LinkResolver(page: fn () => null, article: fn () => null, file: fn () => null);
$pubE = App\Content\Blocks\GalleryList::prepare($e2e, $filesE, $rsv, 'id'); $canE = App\Content\Blocks\GalleryList::prepare($e2e, $filesE, $rsv, 'id', true);
t('ujung ke ujung (Media sungguhan): situs hanya memuat gambar yang ada, URL dari Media::url(); gambar yang dihapus dan berkas PDF dilewati', count($pubE)===1 && $pubE[0]['src']==='https://x.test/storage/galeri/a.jpg' && $pubE[0]['alt']==='A', json_encode($pubE));
t('ujung ke ujung (Media sungguhan): kanvas menampilkan ketiganya dengan penanda [lengkap, hilang, bukan gambar]', count($canE)===3 && array_column($canE,'incomplete')===[false,true,true] && str_contains($canE[1]['note'],'tidak ditemukan') && str_contains($canE[2]['note'],'bukan gambar'), json_encode(array_column($canE,'note')));

echo "\nLinkResolver::make() dengan model sungguhan di dua aplikasi\n";
$pgOn  = DB::table('pages')->insertGetId(['title'=>'{"id":"Tentang","en":"About"}','slug'=>'{"id":"tentang-kami-r","en":"about-r"}','status'=>'online','content'=>'null','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
$pgOff = DB::table('pages')->insertGetId(['title'=>'{"id":"Draf","en":"Draft"}','slug'=>'{"id":"draf-r","en":"draft-r"}','status'=>'offline','content'=>'null','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
$poOn  = DB::table('posts')->insertGetId(['title'=>'{"id":"Artikel","en":"Article"}','slug'=>'{"id":"artikel-r","en":"article-r"}','content'=>'null','status'=>'published','category_id'=>1,'user_id'=>1]);
$poOff = DB::table('posts')->insertGetId(['title'=>'{"id":"Konsep","en":"Draft"}','slug'=>'{"id":"konsep-r","en":"concept-r"}','content'=>'null','status'=>'draft','category_id'=>1,'user_id'=>1]);
$lk = fn (string $kind, int $id, string $loc = 'id') => App\Content\Links\LinkResolver::make()->url(['kind'=>$kind,'ref'=>(string) $id], $loc);
$GLOBALS['__pubcfg'] = [];
$threw = null; try { $r1 = [$lk('page',$pgOn), $lk('article',$poOn)]; } catch (\Throwable $e) { $threw = $e; }
t('CMS tanpa rute publik dan tanpa konfigurasi: tautan halaman/artikel null, TIDAK melempar galat (sebelumnya: route not found)', $threw === null && $r1 === [null, null], $threw ? get_class($threw).': '.$threw->getMessage() : json_encode($r1 ?? null));
$GLOBALS['__pubcfg'] = ['cms.public.base'=>'https://ysbh.org','cms.public.page'=>'/{slug}','cms.public.article'=>'/artikel/{slug}'];
t('dengan konfigurasi: halaman online -> alamat ABSOLUT landing sesuai bahasa', $lk('page',$pgOn)==='https://ysbh.org/tentang-kami-r' && $lk('page',$pgOn,'en')==='https://ysbh.org/about-r');
t('artikel terbit -> /artikel/{slug} di landing, sesuai bahasa', $lk('article',$poOn)==='https://ysbh.org/artikel/artikel-r' && $lk('article',$poOn,'en')==='https://ysbh.org/artikel/article-r');
t('halaman offline, artikel draf, dan ID yang tidak ada -> null', $lk('page',$pgOff)===null && $lk('article',$poOff)===null && $lk('page',999999)===null && $lk('article',999999)===null);
$GLOBALS['__pubcfg'] = ['cms.public.page'=>'/{slug}','cms.public.article'=>'/berita/{slug}'];
t('di landing (tanpa alamat dasar): jalur relatif sesuai templat', $lk('page',$pgOn)==='/tentang-kami-r' && $lk('article',$poOn)==='/berita/artikel-r');
$GLOBALS['__pubcfg'] = ['cms.public.base'=>'https://ysbh.org/cms','cms.public.page'=>'/{slug}'];
t('konfigurasi salah (alamat dasar berisi jalur): null, bukan alamat sisipan', $lk('page',$pgOn)===null);
$GLOBALS['__pubcfg'] = [];

echo "\nPublicLookup: pencarian publik dan perakitan dokumen (dengan model sungguhan)\n";
use App\Content\PublicLookup as PL;
$now = date('Y-m-d H:i:s');
$mkPage = fn (array $slug, string $status, $content = 'null') => DB::table('pages')->insertGetId(['title'=>json_encode(['id'=>'T','en'=>'T']),'slug'=>json_encode($slug),'status'=>$status,'content'=>is_string($content) ? $content : json_encode($content),'created_at'=>$now,'updated_at'=>$now]);
$mkPost = fn (array $o) => DB::table('posts')->insertGetId($o + ['title'=>json_encode(['id'=>'Judul','en'=>'Title']),'slug'=>json_encode(['id'=>'s-'.uniqid(),'en'=>'e-'.uniqid()]),'content'=>'null','status'=>'published','category_id'=>1,'user_id'=>1]);
$pA = $mkPage(['id'=>'tentang-pl','en'=>'about-pl'],'online'); $pB = $mkPage(['id'=>'draf-pl','en'=>'draft-pl'],'offline');
DB::table('pages')->insert(['title'=>'{}','slug'=>'slug-polos-lama','status'=>'online','content'=>'null','created_at'=>$now,'updated_at'=>$now]);
$f1 = PL::findPage('tentang-pl','id'); $f2 = PL::findPage('about-pl','id'); $f3 = PL::findPage('tentang-pl','en');
t('findPage: slug bahasa yang diminta ditemukan', $f1 && $f1['model']->id === $pA && $f1['locale']==='id');
t('findPage: slug dari bahasa LAIN ikut ditemukan, dan bahasa yang cocok dilaporkan (untuk mengatur bahasa halaman)', $f2 && $f2['model']->id === $pA && $f2['locale']==='en' && $f3 && $f3['locale']==='id');
t('findPage: halaman offline, slug tak ada, dan baris lama berisi teks biasa -> null TANPA galat', PL::findPage('draf-pl','id')===null && PL::findPage('tidak-ada','id')===null && PL::findPage('slug-polos-lama','id')===null);
DB::connection()->enableQueryLog(); DB::connection()->flushQueryLog();
foreach (["x' OR '1'='1", 'a b', '<script>', '../etc', "x\0y", '', str_repeat('a', 300), 'UPPER', '-x', 'x--y'] as $badSlug) {
    $threw = null; try { $r = PL::findPage($badSlug,'id'); } catch (\Throwable $e) { $threw = $e; }
    t("findPage: slug tak sah '".substr(str_replace("\0",'\\0',$badSlug),0,20)."' -> null tanpa menyentuh basis data atau melempar galat", $threw === null && $r === null, $threw ? $threw->getMessage() : '');
}
t('slug tak sah: NOL kueri ke basis data (ditolak sebelum menyentuhnya)', count(DB::connection()->getQueryLog()) === 0, (string) count(DB::connection()->getQueryLog()));
DB::connection()->flushQueryLog(); PL::findPage('tentang-pl','id'); $q1 = count(DB::connection()->getQueryLog());
t('slug sah ditemukan di percobaan pertama: SATU kueri; slug yang hanya cocok di bahasa lain: dua kueri', $q1 === 1 && (function () { DB::connection()->flushQueryLog(); PL::findPage('about-pl','id'); return count(DB::connection()->getQueryLog()) === 2; })());
$poOn2 = $mkPost(['slug'=>json_encode(['id'=>'artikel-pl','en'=>'article-pl'])]); $poDraft = $mkPost(['slug'=>json_encode(['id'=>'konsep-pl','en'=>'concept-pl']),'status'=>'draft']);
$a1 = PL::findArticle('artikel-pl','id'); $a2 = PL::findArticle('article-pl','id');
t('findArticle: terbit ditemukan (juga lewat slug bahasa lain); draf -> null; halaman tidak tertukar dengan artikel', $a1 && $a1['model']->id === $poOn2 && $a2 && $a2['locale']==='en' && PL::findArticle('konsep-pl','id')===null && PL::findArticle('tentang-pl','id')===null && PL::findPage('artikel-pl','id')===null);
t("status yang dipakai = status terbit di ContentType (Halaman 'online', Artikel 'published')", PL::PAGE_STATUS === App\Content\ContentType::Page->publishedStatus() && PL::ARTICLE_STATUS === App\Content\ContentType::Article->publishedStatus());

echo "\n-- artikel terbaru\n";
DB::table('posts')->whereIn('status',['published'])->update(['status'=>'draft']);   // kosongkan dulu supaya hitungan pasti
$cat1 = DB::table('categories')->insertGetId(['name'=>json_encode(['id'=>'Kesehatan','en'=>'Health']),'slug'=>json_encode(['id'=>'kesehatan','en'=>'health'])]);
$mk = fn (string $when, array $o = []) => $mkPost($o + ['published_at'=>$when,'category_id'=>$cat1,'featured_image'=>'default.webp','meta_description'=>json_encode(['id'=>'Ringkas','en'=>''])]);
$n1 = $mk('2026-10-01 08:00:00'); $n2 = $mk('2026-10-05 08:00:00'); $n3 = $mk('2026-10-03 08:00:00'); $n4 = $mk('2026-10-08 08:00:00'); $n5 = $mk('2026-09-20 08:00:00'); $nd = $mk('2026-10-09 08:00:00',['status'=>'draft']);
$lt = PL::latestArticles(3);
t('terbaru: hanya yang TERBIT, paling baru dulu, dibatasi jumlah (draf 9 Okt tidak ikut)', array_column($lt['rows'],'id') === [$n4,$n2,$n3], json_encode(array_column($lt['rows'],'id')));
t('terbaru: baris berisi nilai MENTAH kolom; kategori dipetakan id => nama mentah', $lt['rows'][0]['featured_image']==='default.webp' && is_string($lt['rows'][0]['title']) && $lt['categories'][$cat1] === json_encode(['id'=>'Kesehatan','en'=>'Health']), json_encode($lt['categories']));
t('terbaru: batas 1..12 (0 -> 1, 99 -> 12) dan pengecualian satu artikel (mis. yang sedang dibuka)', count(PL::latestArticles(0)['rows'])===1 && count(PL::latestArticles(99)['rows'])===5 && !in_array($n4, array_column(PL::latestArticles(3,$n4)['rows'],'id')) && array_column(PL::latestArticles(3,$n4)['rows'],'id')===[$n2,$n3,$n1]);
$cards = App\Content\Blocks\ArticleCards::prepare($lt['rows'], $lt['categories'], 'id', fn ($s) => App\Content\Links\LinkResolver::publicUrl($s,'/artikel/{slug}','https://ysbh.org'), fn ($f) => null, fn ($i) => null);
t('terbaru -> kartu: kategori "Kesehatan", ringkasan dari meta, tanggal, tautan absolut; sampul bawaan = tanpa gambar', count($cards)===3 && $cards[0]['category']==='Kesehatan' && $cards[0]['excerpt']==='Ringkas' && $cards[0]['dateLabel']==='8 Okt 2026' && str_starts_with($cards[0]['url'],'https://ysbh.org/artikel/') && $cards[0]['cover']===null);
DB::table('posts')->where('id',$n4)->update(['featured_image'=>'cover-n4.webp']);
$cards2 = App\Content\Blocks\ArticleCards::prepare(PL::latestArticles(1)['rows'], [], 'id', fn ($s) => null, fn ($f) => App\Content\Links\LinkResolver::publicUrl($f,'/storage/posts/{file}','https://ysbh.org','{file}'), fn ($i) => null);
t('sampul nama berkas -> URL dari templat sampul', $cards2[0]['cover']==='https://ysbh.org/storage/posts/cover-n4.webp');

echo "\n-- perakitan dokumen: snippet sisipan dan penutup\n";
$blk = fn (string $id, string $type, array $data) => [$id => ['id'=>$id,'type'=>$type,'data'=>$data]];
$mkSn = fn (string $key, bool $closing, int $sort, string $status, array $blocks, array $order) => DB::table('snippets')->insertGetId(['key'=>$key,'title'=>json_encode(['id'=>$key,'en'=>$key]),'content'=>json_encode(['blocks'=>$blocks,'order'=>$order,'settings'=>[]]),'status'=>$status,'is_closing'=>$closing ? 1 : 0,'sort_order'=>$sort,'created_at'=>$now,'updated_at'=>$now]);
DB::table('snippets')->delete();
$sDon = $mkSn('donasi',true,1,'online',$blk('sd1','heading',['text'=>['id'=>'Donasi']]),['sd1']);
$sKon = $mkSn('hubungi',true,2,'online',$blk('sk1','button-builder',['buttons'=>[['label'=>['id'=>'Hubungi'],'link'=>['kind'=>'url','ref'=>'javascript:alert(1)']]]]) + $blk('sk2','heading',['text'=>['id'=>'Kontak']]),['sk1','sk2']);
$sOff = $mkSn('draf-penutup',true,3,'offline',$blk('so1','heading',['text'=>['id'=>'JANGAN TAMPIL']]),['so1']);
$sFree = $mkSn('promo',false,4,'online',$blk('sp1','heading',['text'=>['id'=>'Promo']]),['sp1']);
$page = fn (array $settings, array $extraBlocks = [], array $order = ['p1']) => ['blocks'=>$blk('p1','paragraph',['text'=>['id'=>'Isi halaman']]) + $extraBlocks,'order'=>$order,'settings'=>$settings];
$d = PL::document($page([]));
t('bawaan: semua snippet penutup ONLINE ditambahkan di akhir menurut sort_order; yang offline dan non-penutup tidak', $d['order']===['p1','sd1','sk1','sk2'], json_encode($d['order']));
t('isi snippet DIBERSIHKAN (tautan javascript: di tombol penutup dikosongkan)', $d['blocks']['sk1']['data']['buttons'][0]['link']['ref']==='');
t("settings.closing = 'none' / false / [] -> tanpa penutup", PL::document($page(['closing'=>'none']))['order']===['p1'] && PL::document($page(['closing'=>false]))['order']===['p1'] && PL::document($page(['closing'=>[]]))['order']===['p1']);
t('settings.closing = ["hubungi"] -> hanya itu; boleh menyebut snippet non-penutup ("promo"); kunci tak dikenal/offline diabaikan', PL::document($page(['closing'=>['hubungi']]))['order']===['p1','sk1','sk2'] && PL::document($page(['closing'=>['promo','draf-penutup','tidak-ada']]))['order']===['p1','sp1']);
$inl = $page([], $blk('x1','snippet',['snippet_id'=>$sDon]) + $blk('p2','paragraph',['text'=>['id'=>'Akhir']]), ['x1','p1','p2']);
$di = PL::document($inl);
t('blok snippet diperluas DI TEMPATNYA; blok pembungkusnya dibuang; snippet itu tidak ditambahkan lagi sebagai penutup', $di['order']===['sd1','p1','p2','sk1','sk2'] && !isset($di['blocks']['x1']), json_encode($di['order']));
$dup = PL::document($page([], $blk('x1','snippet',['snippet_id'=>$sDon]) + $blk('x2','snippet',['snippet_id'=>$sDon]) + $blk('x3','snippet',['snippet_id'=>$sOff]) + $blk('x4','snippet',['snippet_id'=>999999]), ['x1','x2','x3','x4','p1']));
t('snippet sama disisipkan dua kali: tampil sekali; id snippet offline/tak ada: blok dibuang diam-diam; penutup tetap menyusul', $dup['order']===['sd1','p1','sk1','sk2'] && !isset($dup['blocks']['x2'],$dup['blocks']['so1']), json_encode($dup['order']));
$nested = $mkSn('kolom',false,5,'online',$blk('c1','multi-columns',['col_1_zone'=>['c2'],'col_2_zone'=>[]]) + $blk('c2','heading',['text'=>['id'=>'Anak kolom']]),['c1']);
$dn = PL::document($page([], $blk('x1','snippet',['snippet_id'=>$nested]), ['x1','p1'])+[]);
t('snippet berisi kolom: anak-anak kolomnya IKUT terbawa (rujukan zona tetap sah)', isset($dn['blocks']['c1'],$dn['blocks']['c2']) && in_array('c1',$dn['order'],true) && !in_array('c2',$dn['order'],true), json_encode($dn['order']));
t('tanpa snippet apa pun di basis data: dokumen sama dengan isi sendiri (tanpa galat)', (function () use ($page) { DB::table('snippets')->delete(); $r = PL::document($page([])); return $r['order']===['p1'] && $r['blocks']['p1']['data']['text']['id']==='Isi halaman'; })());
t('$withSnippets = false (mis. isi sebuah snippet): tidak ada perakitan', PL::document($page([]), ['id','en'], false)['order']===['p1']);
t('isi kosong / null / teks lama (HTML): tanpa galat; HTML lama menjadi satu paragraf dan ditandai imported', PL::document(null)['order']===[] && PL::document('')['order']===[] && PL::document('<p>Lama</p>')['imported']===true && count(PL::document('<p>Lama</p>')['order'])===1);

echo "\nSavedPreview memakai perakit yang sama dengan situs publik\n";
$snC = DB::table('snippets')->insertGetId(['key'=>'penutup-sp','title'=>json_encode(['id'=>'P','en'=>'P']),'content'=>json_encode(['blocks'=>['c1'=>['id'=>'c1','type'=>'heading','data'=>['text'=>['id'=>'Penutup tampil']]]],'order'=>['c1'],'settings'=>[]]),'status'=>'online','is_closing'=>1,'sort_order'=>1,'created_at'=>$now,'updated_at'=>$now]);
$pgSP = $mkPage(['id'=>'sp-pl','en'=>'sp-pl-en'],'offline',['blocks'=>$blk('p1','paragraph',['text'=>['id'=>'Isi']]),'order'=>['p1'],'settings'=>[]]);
$svp = App\Content\SavedPreview::from(App\Content\ContentType::Page, Page::find($pgSP), ['id','en'], 'id');
t('pratinjau tersimpan HALAMAN: snippet penutup ikut di akhir (sama dengan situs publik)', $svp['order']===['p1','c1'], json_encode($svp['order']));
$svpA = App\Content\SavedPreview::from(App\Content\ContentType::Article, Post::find($poOn2), ['id','en'], 'id');
t('pratinjau tersimpan ARTIKEL: juga mengikuti snippet penutup', in_array('c1', $svpA['order'], true));
$snOwn = DB::table('snippets')->where('id',$snC)->first();
$svpS = App\Content\SavedPreview::from(App\Content\ContentType::Snippet, Snippet::find($snC), ['id','en'], 'id');
$snX = DB::table('snippets')->insertGetId(['key'=>'bukan-penutup-sp','title'=>json_encode(['id'=>'X','en'=>'X']),'content'=>json_encode(['blocks'=>['x1'=>['id'=>'x1','type'=>'heading','data'=>['text'=>['id'=>'Milik sendiri']]]],'order'=>['x1'],'settings'=>[]]),'status'=>'online','is_closing'=>0,'sort_order'=>9,'created_at'=>$now,'updated_at'=>$now]);
$svpS2 = App\Content\SavedPreview::from(App\Content\ContentType::Snippet, Snippet::find($snX), ['id','en'], 'id');
t('pratinjau tersimpan SNIPPET: hanya isinya sendiri; snippet penutup LAIN tidak ditambahkan ke isi sebuah snippet', $svpS['order']===['c1'] && $svpS2['order']===['x1'], json_encode([$svpS['order'],$svpS2['order']]));

echo "\nArtikel Terbaru di jalur simpan dan perakitan\n";
$evilLA = ['title'=>['id'=>"  Usia <5\ttahun\n",'fr'=>'bocor'],'limit'=>99,'columns'=>'x" onload="1','show_image'=>'','all_label'=>['id'=>str_repeat('z',100)],'all_link'=>['kind'=>'url','ref'=>'javascript:alert(1)']];
$pgLA = new Page;
ContentWriter::fill($pgLA, T::Page, $state(['status'=>'offline','slug'=>['id'=>'la-uji','en'=>'la-test'],'titles'=>['id'=>'LA','en'=>'LA'],'blocks'=>['l1'=>['id'=>'l1','type'=>'latest-articles-builder','data'=>$evilLA]],'order'=>['l1'],'locales'=>['id','en']]));
$pgLA->save(); $stLA = Page::find($pgLA->id)->content['blocks']['l1']['data'];
t('penulis record membersihkan Artikel Terbaru: jumlah/kolom tak sah -> bawaan (teks); bendera boolean; tautan javascript: dikosongkan; teks medis "<5" utuh', $stLA['limit']==='3' && $stLA['columns']==='3' && $stLA['show_image']===false && $stLA['all_link']['ref']==='' && $stLA['title']['id']==='Usia <5 tahun' && mb_strlen($stLA['all_label']['id'])===60, json_encode($stLA));
t('data bersih lolos pembersih lagi tanpa berubah lewat basis data (idempoten)', \App\Editor\Blocks\LatestArticlesBlock::sanitize($stLA, ['id','en']) === $stLA);
$dLA = PL::document(['blocks'=>['l1'=>['id'=>'l1','type'=>'latest-articles-builder','data'=>$evilLA]],'order'=>['l1'],'settings'=>[]]);
t('perakit dokumen publik membersihkan blok dinamis ini juga (halaman publik tidak pernah menerima data mentah)', $dLA['blocks']['l1']['data']['columns']==='3' && $dLA['blocks']['l1']['data']['all_link']['ref']==='');

echo "\nDaftar artikel berhalaman (articlesPage)\n";
DB::table('posts')->update(['status'=>'draft']);
$pg = fn (int $n) => DB::table('posts')->insertGetId(['title'=>json_encode(['id'=>"A$n",'en'=>"A$n"]),'slug'=>json_encode(['id'=>"a-$n",'en'=>"a-$n-en"]),'content'=>'null','status'=>'published','category_id'=>1,'user_id'=>1,'published_at'=>sprintf('2026-09-%02d 08:00:00',$n),'featured_image'=>'default.webp']);
$ids = []; for ($n = 1; $n <= 7; $n++) $ids[$n] = $pg($n);
$tie = DB::table('posts')->insertGetId(['title'=>json_encode(['id'=>'Kembar']),'slug'=>json_encode(['id'=>'kembar']),'content'=>'null','status'=>'published','category_id'=>1,'user_id'=>1,'published_at'=>'2026-09-07 08:00:00','featured_image'=>'default.webp']);
$p1 = PL::articlesPage(1, 3); $p2 = PL::articlesPage(2, 3); $p3 = PL::articlesPage(3, 3);
t('halaman 1 berisi 3 artikel terbaru; total = 8 terbit; urutan: tanggal menurun, lalu id menurun bila sama', array_column($p1['rows'],'id') === [$tie,$ids[7],$ids[6]] && $p1['total'] === 8 && $p1['page']===1 && $p1['perPage']===3, json_encode(array_column($p1['rows'],'id')));
t('halaman 2 dan 3 melanjutkan tanpa tumpang tindih; halaman terakhir memuat sisanya (2)', array_column($p2['rows'],'id') === [$ids[5],$ids[4],$ids[3]] && array_column($p3['rows'],'id') === [$ids[2],$ids[1]]);
t('halaman melewati akhir -> baris kosong (komponen memutuskan 404); halaman < 1 -> 1', PL::articlesPage(99,3)['rows'] === [] && PL::articlesPage(0,3)['page']===1 && PL::articlesPage(-5,3)['page']===1);
t('perPage dibatasi 1..24; draf tidak ikut hitungan', PL::articlesPage(1,0)['perPage']===1 && count(PL::articlesPage(1,0)['rows'])===1 && PL::articlesPage(1,999)['perPage']===24 && PL::articlesPage(1,24)['total']===8);
t('latestArticles tetap bekerja setelah refactor (urutan, batas, pengecualian)', array_column(PL::latestArticles(2)['rows'],'id') === [$tie,$ids[7]] && !in_array($tie, array_column(PL::latestArticles(3,$tie)['rows'],'id')));

echo "\n==> $ok lulus, $bad gagal\n";
}
