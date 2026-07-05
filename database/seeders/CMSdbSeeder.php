<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CMSdbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
         //buat user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'job_title' =>'Gembel',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Alfrida Pabasi',
            'email' => 'midar@mail.com',
            'job_title' =>'Ketua Yayasan',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'ELizabeth Kristiani',
            'email' => 'liza@mail.com',
            'job_title' =>'Bendahara',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Ruth Charlota Yakoba Fouw',
            'email' => 'utha@example.com',
            'job_title' =>'Program Officer Kesehatan Ibu & Anak',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Leon Dolfus Mangonto',
            'email' => 'leon@mail.com',
            'job_title' =>'Program Officer KIA',
            'password' => Hash::make('husada'),
        ]);
        // Membuat role
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleEditor = Role::create(['name' => 'editor']);
        $roleWriter = Role::create(['name' => 'writer']);

        // Memberikan role ke user
        $user2 = User::find(2);
        $user2->assignRole('admin');

        // Memberikan multi-role
        $user3 = User::find(3);
        $user3->assignRole(['admin', 'editor']);

        // Memberikan multi-role
        $user4 = User::find(4);
        $user4->assignRole(['writer']);

        // Memberikan multi-role
        $user5 = User::find(5);
        $user5->assignRole(['writer']);

        // 2. Buat Kategori
        $category = Category::firstOrCreate(['name' => 'Berita'], ['slug' => 'berita']);
        $category = Category::firstOrCreate(['name' => 'Siaran Pers'], ['slug' => 'siaran-pers']);
        $category = Category::firstOrCreate(['name' => 'Pengumuman'], ['slug' => 'pengumuman']);
        $category = Category::firstOrCreate(['name' => 'Program & Kampanye'], ['slug' => 'program']);
        $category = Category::firstOrCreate(['name' => 'Kisah Inspiratif'], ['slug' => 'cerita']);
        $category = Category::firstOrCreate(['name' => 'Laporan & Transparansi'], ['slug' => 'laporan']);
        $category = Category::firstOrCreate(['name' => 'Tulisan Edukasi'], ['slug' => 'blog']);
        $category = Category::firstOrCreate(['name' => 'Acara & Kegiatan'], ['slug' => 'events']);
        //artikel
        //melaporkan [penelitian dan laporan]

        // 3. Buat beberapa Tag
        $tags = collect([
            'Nutrisi', 
            'Lansia', 
            'Posyandu', 
            'Kota Jayapura',
            'Kab. Jayapura',
            'Kuda Menjangan',
            'Aku Papua',
            'Edo Kondologit',
            ])->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName], ['slug' => Str::slug($tagName)]);
        });

        // 4. Buat Artikel
        // $post = Post::create([
        //     'user_id' => $user4->id, // Penulis utama (di tabel posts)
        //     'category_id' => $category->id,
        //     'title' => 'Panduan Gizi Sehat untuk Lansia di Papua',
        //     'slug' => Str::slug('Panduan Gizi Sehat untuk Lansia di Papua'),
        //     'content' => 'Isi konten artikel yang sangat bermanfaat...',
        //     'featured_image' => 'covers/gizi-lansia.jpg',
        //     'status' => 'published',
        //     'published_at' => now(),
        // ]);

        // 5. Hubungkan Multiple Writers (Sitasi)
        // Kita hubungkan penulis utama dan penulis pembantu ke tabel pivot post_user
        // $post->authors()->sync([
        //     $user3->id => ['is_primary' => true],
        //     $user4->id => ['is_primary' => false],
        //     // $user3->id => ['is_primary' => false]
        // ]);

        // // 6. Hubungkan Multiple Tags
        // $post->tags()->sync($tags->pluck('id'));
    }
}
