<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Navigation;
use App\Models\Post;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class FillerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $date = date('Y-m-d H:i:s');
         //buat user
        User::factory()->create([
            'name' => 'Test User',
            'handle' => 'guest',
            'active' => true,
            'email' => 'test@example.com',
            'job_title' =>'Staff',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Alfrida Pabasi',
            'handle' => 'apabasi',
            'active' => true,
            'email' => 'midar@mail.com',
            'job_title' =>'Ketua Yayasan',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'ELizabeth Kristiani',
            'handle' => 'ekristiani',
            'active' => true,
            'email' => 'liza@mail.com',
            'job_title' =>'Bendahara',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Ruth Charlota Yakoba Fouw',
            'handle' => 'rfouw',
            'active' => true,
            'email' => 'utha@example.com',
            'job_title' =>'Program Officer Kesehatan Ibu & Anak',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Leon Dolfus Mangonto',
            'handle' => 'lmangonto',
            'active' => false,
            'email' => 'leon@mail.com',
            'job_title' =>'Program Officer KIA',
            'password' => Hash::make('husada'),
        ]);
        // Membuat role
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleEditor = Role::create(['name' => 'editor']);
        $roleWriter = Role::create(['name' => 'writer']);

        // Memberikan role ke user
        $user1 = User::find(1);
        $user1->assignRole('admin');
        $user1->assignRole('editor');
        $user1->assignRole('writer');

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

        

        Category::firstOrCreate(
            [ 'slug->en' => 'news', ],
            [   'slug' => [
                    'id' => 'berita',
                    'en' => 'news'
                    ],
                'name'   => [
                    'id' => 'Berita',
                    'en' => 'News'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 08:00:00'),
            ]
        );
        Category::firstOrCreate(
            [   'slug->en' => 'press-release', ],
            [   'slug' => [
                    'id' => 'siaran-pers',
                    'en' => 'press-release'
                    ],
                'name'   => [
                    'id' => 'Siaran Pers',
                    'en' => 'Press Release'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 09:00:00'),
            ]
        );
        Category::firstOrCreate(
            [   'slug->en' => 'announcement', ],
            [   'slug' => [
                    'id' => 'pengumuman',
                    'en' => 'announcement'
                    ],
                'name'   => [
                    'id' => 'Pengumuman',
                    'en' => 'Announcement'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 09:00:00'),
            ]
        );
        Category::firstOrCreate(
            [   'slug->en' => 'program', ],
            [   'slug' => [
                    'id' => 'rancangan',
                    'en' => 'program'
                    ],
                'name'   => [
                    'id' => 'Rancangan & Kampanye',
                    'en' => 'Program & Campaign'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 11:00:00'),
            ]
        );
        Category::firstOrCreate(
            [   'slug->en' => 'stories', ],
            [   'slug' => [
                    'id' => 'cerita',
                    'en' => 'stories'
                    ],
                'name'   => [
                    'id' => 'Kisah Inspiratif',
                    'en' => 'Inspirational Story'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 12:00:00'),
            ]
        );
        Category::firstOrCreate(
            [   'slug->en' => 'reports', ],
            [   'slug' => [
                    'id' => 'laporan',
                    'en' => 'reports'
                    ],
                'name'   => [
                    'id' => 'Laporan & Transparansi',
                    'en' => 'Reports & Transparancies'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 13:00:00'),
            ]
        );
        Category::firstOrCreate(
            [   'slug->en' => 'events', ],
            [   'slug' => [
                    'id' => 'kegiatan',
                    'en' => 'events'
                    ],
                'name'   => [
                    'id' => 'Acara & Kegiatan',
                    'en' => 'Events'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 14:00:00'),
            ]
        );

        // 2. Buat Kategori
        // $category = Category::firstOrCreate(['name' => 'Berita'], ['slug' => 'berita']);
        // $category = Category::firstOrCreate(['name' => 'Siaran Pers'], ['slug' => 'siaran-pers']);
        // $category = Category::firstOrCreate(['name' => 'Pengumuman'], ['slug' => 'pengumuman']);
        // $category = Category::firstOrCreate(['name' => 'Program & Kampanye'], ['slug' => 'program']);
        // $category = Category::firstOrCreate(['name' => 'Kisah Inspiratif'], ['slug' => 'cerita']);
        // $category = Category::firstOrCreate(['name' => 'Laporan & Transparansi'], ['slug' => 'laporan']);
        // $category = Category::firstOrCreate(['name' => 'Tulisan Edukasi'], ['slug' => 'blog']);
        // $category = Category::firstOrCreate(['name' => 'Acara & Kegiatan'], ['slug' => 'events']);
        //artikel
        //melaporkan [penelitian dan laporan]

        Tag::firstOrCreate(
            [   'slug->en' => 'nutrition', ],
            [   'slug' => [
                    'id' => 'nutrisi',
                    'en' => 'nutrition'
                    ],
                'name'   => [
                    'id' => 'Nutrisi',
                    'en' => 'Nutrition'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
            ]
        );
        Tag::firstOrCreate(
            [   'slug->en' => 'elderly', ],
            [   'slug' => [
                    'id' => 'lansia',
                    'en' => 'elderly'
                    ],
                'name'   => [
                    'id' => 'Lansia',
                    'en' => 'Elderly'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
            ]
        );
        Tag::firstOrCreate(
            [   'slug->en' => 'integrated-health-post', ],
            [   'slug' => [
                    'id' => 'posyandu',
                    'en' => 'integrated-health-post'
                    ],
                'name'   => [
                    'id' => 'Posyandu',
                    'en' => 'Integrated Health Post'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
            ]
        );
        Tag::firstOrCreate(
            [   'slug->en' => 'jayapura-city', ],
            [   'slug' => [
                    'id' => 'kota-jayapura',
                    'en' => 'jayapura-city'
                    ],
                'name'   => [
                    'id' => 'Kota Jayapura',
                    'en' => 'Jayapura City'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
            ]
        );
        Tag::firstOrCreate(
            [   'slug->en' => 'jayapura-regency', ],
            [   'slug' => [
                    'id' => 'kota-jayapura',
                    'en' => 'jayapura-regency'
                    ],
                'name'   => [
                    'id' => 'Kabupaten Jayapura',
                    'en' => 'Jayapura Regency'
                    ],
                'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
            ]
        );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'home', ],
        //     [   'label' => [
        //             'id' => 'Beranda',
        //             'en' => 'Home'
        //             ],
        //         'route_name' => 'home',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'programs', ],
        //     [   'label' => [
        //             'id' => 'Program',
        //             'en' => 'Programs'
        //             ],
        //         'route_name' => 'programs',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'transparancies', ],
        //     [   'label' => [
        //             'id' => 'Transparansi',
        //             'en' => 'Transparancies'
        //             ],
        //         'route_name' => 'transparancies',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'credibility', ],
        //     [   'label' => [
        //             'id' => 'Kredibilitas',
        //             'en' => 'Credibility'
        //             ],
        //         'route_name' => 'credibility',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'contact', ],
        //     [   'label' => [
        //             'id' => 'Kredibilitas',
        //             'en' => 'Credibility'
        //             ],
        //         'route_name' => 'contact',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'about', ],
        //     [   'label' => [
        //             'id' => 'Tentang Kami',
        //             'en' => 'About Us'
        //             ],
        //         'route_name' => 'about',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );
        // Navigation::firstOrCreate(
        //     [   'route_name' => 'impact', ],
        //     [   'label' => [
        //             'id' => 'Dampak',
        //             'en' => 'Impact'
        //             ],
        //         'route_name' => 'impact',
        //         'url' => null,
        //         'order' => 0,
        //         'is_active' => true,
        //         'created_at'        => \Carbon\Carbon::parse('31 July 2026 00:00:00'),
        //     ]
        // );


    }
}
