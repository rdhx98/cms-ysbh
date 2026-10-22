<?php

namespace App\Content;

/**
 * Kerangka lima halaman tambahan (rilis 84): Struktur Organisasi, Sarana dan Inventaris, Mitra dan Donatur, FAQ, Penelitian. DATA MURNI,
 * digabung oleh SiteText::all() dan dibaca SitePages::content seperti halaman lain.
 *
 * Beda dengan 13 halaman pertama: selain judul (h1/h2/h3) dan paragraf (p), kerangka ini memakai blok tata letak yang sudah ada, supaya
 * penulis tinggal mengisi, bukan menyusun ulang:
 *   ['accordion', ['items' => [[tanya, jawab], ...]]]                     blok Akordion / FAQ
 *   ['table', ['caption' => peta, 'columns' => [[peta, rata], ...], 'rows' => [[peta, peta, ...], ...]]]   blok Tabel
 *   ['callout', ['tone' => 'info', 'title' => peta, 'body' => peta]]      blok Callout
 *   ['latest', [... data blok Artikel terbaru ...]]                        blok Artikel terbaru
 * "peta" = ['en' => ..., 'id' => ...] (teks polos untuk blok; HTML hanya di paragraf). Bentuk datanya dibersihkan oleh modul blok masing-masing
 * (SitePages::content memanggil sanitize), jadi nilai yang tidak sah tidak mungkin tersimpan.
 *
 * Yang SENGAJA tidak ada: nama pengurus, angka inventaris, nama donatur, jawaban FAQ yang belum diputuskan yayasan. Semuanya berpenanda
 * [ISI-DULU] (halaman tetap offline sampai penanda hilang: cms:audit-placeholders). Fakta yang dipakai sudah ada di halaman lain (mitra
 * pelaksana, wilayah kerja, imbauan penipuan donasi). Tidak ada tautan luar, email, atau nomor.
 */
final class SiteTextExtra
{
    private const T = Placeholders::TOKEN;

    /** @return array{en:string,id:string} */
    private static function m(string $en, string $id): array
    {
        return ['en' => $en, 'id' => $id];
    }

    /** Peta dengan penanda di depan. */
    private static function todo(string $en, string $id): array
    {
        return self::m(self::T . ' ' . $en, self::T . ' ' . $id);
    }

    /** @return array<string,list<array{0:string,1:mixed}>> */
    public static function all(): array
    {
        $m = fn (string $en, string $id): array => self::m($en, $id);
        $todo = fn (string $en, string $id): array => self::todo($en, $id);
        $T = self::T;
        // Satu baris tabel Inventaris: nama, jumlah, kondisi, lokasi, tahun
        $row = fn (array $item, array $qty, array $cond, array $where, array $year): array => [$item, $qty, $cond, $where, $year];
        $inventoryColumns = [
            [$m('Item', 'Barang'), 'left'], [$m('Quantity', 'Jumlah'), 'center'], [$m('Condition', 'Kondisi'), 'left'],
            [$m('Location', 'Lokasi'), 'left'], [$m('Year acquired', 'Tahun perolehan'), 'center'],
        ];
        $blank = $todo('fill in', 'isi');

        return [
            'structure' => [
                ['h1', $m('Organizational structure', 'Struktur organisasi')],
                ['p', $m(
                    'Yayasan Sinar Bhakti Husada is led by a Board of Trustees (Pembina), a Management Board (Pengurus) and a Supervisory Board (Pengawas), as set out in the foundation\'s deed of establishment. This page shows who they are and how the foundation is organized.',
                    'Yayasan Sinar Bhakti Husada dipimpin oleh Pembina, Pengurus, dan Pengawas sebagaimana diatur dalam akta pendirian yayasan. Halaman ini menunjukkan siapa mereka dan bagaimana yayasan diorganisasi.'
                )],
                ['p', $todo('Confirm the names and positions below against the latest deed and its amendments before publishing.', 'Cocokkan nama dan jabatan di bawah dengan akta terakhir dan perubahannya sebelum diterbitkan.')],
                ['h2', $m('Organization chart', 'Bagan organisasi')],
                ['p', $m(
                    $T . ' <em>Photo: Organization chart of the foundation (upload as an image and write a text description for screen readers)</em>',
                    $T . ' <em>Foto: Bagan organisasi yayasan (unggah sebagai gambar dan tulis deskripsi teks untuk pembaca layar)</em>'
                )],
                ['h2', $m('Foundation organs', 'Organ yayasan')],
                ['table', [
                    'caption' => $m('Members of the foundation\'s organs', 'Anggota organ yayasan'),
                    'columns' => [[$m('Position', 'Jabatan'), 'left'], [$m('Name', 'Nama'), 'left'], [$m('Since', 'Sejak'), 'center']],
                    'rows' => [
                        [$m('Board of Trustees (Pembina)', 'Pembina'), $todo('Name', 'Nama'), $todo('Year', 'Tahun')],
                        [$m('Chair of the Management Board', 'Ketua Pengurus'), $todo('Name', 'Nama'), $todo('Year', 'Tahun')],
                        [$m('Secretary', 'Sekretaris'), $todo('Name', 'Nama'), $todo('Year', 'Tahun')],
                        [$m('Treasurer', 'Bendahara'), $todo('Name', 'Nama'), $todo('Year', 'Tahun')],
                        [$m('Supervisory Board (Pengawas)', 'Pengawas'), $todo('Name', 'Nama'), $todo('Year', 'Tahun')],
                    ],
                ]],
                ['p', $todo('Add each person\'s photo and a two-sentence background in the editor (for example with Cards), only with their written consent.', 'Tambahkan foto dan latar belakang singkat (dua kalimat) tiap orang di editor (mis. dengan Kartu), hanya dengan persetujuan tertulis mereka.')],
                ['h2', $m('Core team', 'Tim inti')],
                ['p', $todo('List key staff: name, position, field of work, year joined, photo (with consent).', 'Daftar staf kunci: nama, jabatan, bidang kerja, tahun bergabung, foto (dengan persetujuan).')],
                ['p', $m(
                    $T . ' <em>Photo: The YSBH core team</em>',
                    $T . ' <em>Foto: Tim inti YSBH</em>'
                )],
                ['h2', $m('Accountability', 'Akuntabilitas')],
                ['p', $m(
                    'Learn more about how we are held accountable on the <a href="internal://page/transparency">Transparency</a> page, and about our recognition and track record on the <a href="internal://page/credibility">Credibility</a> page.',
                    'Pelajari bagaimana kami dipertanggungjawabkan di halaman <a href="internal://page/transparansi">Transparansi</a>, serta pengakuan dan rekam jejak kami di halaman <a href="internal://page/kredibilitas">Kredibilitas</a>.'
                )],
            ],

            'inventory' => [
                ['h1', $m('Facilities and inventory', 'Sarana prasarana dan inventaris')],
                ['p', $m(
                    'This page lists the main facilities and equipment YSBH uses to run its programs, so that partners and donors can see what is in place. Monetary values and serial numbers are not listed here.',
                    'Halaman ini mendaftar sarana dan peralatan utama yang dipakai YSBH untuk menjalankan program, agar mitra dan donatur dapat melihat apa yang sudah tersedia. Nilai rupiah dan nomor seri tidak dicantumkan di sini.'
                )],
                ['callout', [
                    'tone' => 'info',
                    'title' => $m('Data as of', 'Data per tanggal'),
                    'body' => $todo('Date of the latest inventory check (day, month, year). Update this page at least once a year.', 'Tanggal pemeriksaan inventaris terakhir (hari, bulan, tahun). Perbarui halaman ini sekurang-kurangnya setahun sekali.'),
                ]],
                ['h2', $m('Offices and workspaces', 'Kantor dan ruang kerja')],
                ['table', [
                    'caption' => $m('Offices and workspaces', 'Kantor dan ruang kerja'),
                    'columns' => $inventoryColumns,
                    'rows' => [
                        $row($m('Main office', 'Kantor utama'), $m('1', '1'), $blank, $m('Abepura, Jayapura', 'Abepura, Jayapura'), $blank),
                        $row($m('Field office', 'Kantor lapangan'), $blank, $blank, $blank, $blank),
                        $row($todo('Other (storage, meeting room, ...)', 'Lainnya (gudang, ruang rapat, ...)'), $blank, $blank, $blank, $blank),
                    ],
                ]],
                ['h2', $m('Vehicles and field transport', 'Kendaraan dan transportasi lapangan')],
                ['table', [
                    'caption' => $m('Vehicles and field transport', 'Kendaraan dan transportasi lapangan'),
                    'columns' => $inventoryColumns,
                    'rows' => [
                        $row($todo('Vehicle type', 'Jenis kendaraan'), $blank, $blank, $blank, $blank),
                        $row($todo('Vehicle type', 'Jenis kendaraan'), $blank, $blank, $blank, $blank),
                    ],
                ]],
                ['h2', $m('Health and training equipment', 'Peralatan kesehatan dan pelatihan')],
                ['table', [
                    'caption' => $m('Health and training equipment', 'Peralatan kesehatan dan pelatihan'),
                    'columns' => $inventoryColumns,
                    'rows' => [
                        $row($todo('Equipment', 'Peralatan'), $blank, $blank, $blank, $blank),
                        $row($todo('Equipment', 'Peralatan'), $blank, $blank, $blank, $blank),
                        $row($todo('Equipment', 'Peralatan'), $blank, $blank, $blank, $blank),
                    ],
                ]],
                ['h2', $m('Photos', 'Foto')],
                ['p', $m($T . ' <em>Photo: The YSBH main office</em>', $T . ' <em>Foto: Kantor utama YSBH</em>')],
                ['p', $m($T . ' <em>Photo: Equipment used in the field</em>', $T . ' <em>Foto: Peralatan yang dipakai di lapangan</em>')],
            ],

            'partners' => [
                ['h1', $m('Partners and donors', 'Mitra dan donatur')],
                ['p', $m(
                    'Our work is made possible by government, international and community partners, and by donors who share our commitment to health in Papua.',
                    'Pekerjaan kami dimungkinkan oleh mitra pemerintah, internasional, dan masyarakat, serta donatur yang sama-sama berkomitmen pada kesehatan di Papua.'
                )],
                ['h2', $m('Development and implementing partners', 'Mitra pembangunan dan pelaksana')],
                ['table', [
                    'caption' => $m('Partners we have worked with since 2014', 'Mitra yang bekerja sama dengan kami sejak 2014'),
                    'columns' => [[$m('Partner', 'Mitra'), 'left'], [$m('Type', 'Jenis'), 'left'], [$m('What we did together', 'Yang dikerjakan bersama'), 'left']],
                    'rows' => [
                        [$m('UNICEF', 'UNICEF'), $m('United Nations agency', 'Badan PBB'), $todo('Programs and years', 'Program dan tahun')],
                        [$m('USAID (through Pact and FHI 360)', 'USAID (melalui Pact dan FHI 360)'), $m('Development agency', 'Lembaga pembangunan'), $todo('Programs and years', 'Program dan tahun')],
                        [$m('World Health Organization (WHO)', 'Organisasi Kesehatan Dunia (WHO)'), $m('United Nations agency', 'Badan PBB'), $todo('Programs and years', 'Program dan tahun')],
                        [$m('Provincial and district health offices in Papua', 'Dinas kesehatan provinsi dan kabupaten di Papua'), $m('Government', 'Pemerintah'), $todo('Districts and scope', 'Kabupaten dan ruang lingkup')],
                    ],
                ]],
                ['h2', $m('Government agreements', 'Kerja sama dengan pemerintah')],
                ['p', $todo('List formal agreements (MoU) with health offices or health centers: office, year, scope. Publish only with the other party\'s permission.', 'Daftar kerja sama formal (MoU) dengan dinas atau puskesmas: instansi, tahun, ruang lingkup. Terbitkan hanya dengan izin pihak lain.')],
                ['h2', $m('Academic and NGO partners', 'Mitra akademik dan LSM')],
                ['p', $todo('List universities, research institutions and NGOs we work with, with logos only if written permission is given.', 'Daftar perguruan tinggi, lembaga penelitian, dan LSM yang bekerja sama dengan kami; logo hanya bila ada izin tertulis.')],
                ['h2', $m('Donors', 'Donatur')],
                ['p', $m(
                    'We thank every donor who supports our work. Corporate and individual donors are listed only with their written permission.',
                    'Kami berterima kasih kepada setiap donatur yang mendukung pekerjaan kami. Donatur perusahaan dan perorangan hanya dicantumkan dengan izin tertulis mereka.'
                )],
                ['p', $todo('Donor names or logos that agreed to be shown.', 'Nama atau logo donatur yang setuju ditampilkan.')],
                ['p', $m($T . ' <em>Photo: Partner and donor logos</em>', $T . ' <em>Foto: Logo mitra dan donatur</em>')],
                ['h2', $m('Work with us', 'Bekerja sama dengan kami')],
                ['p', $m(
                    'Want to support or partner with us? <a href="internal://page/donate">Donate →</a> · <a href="internal://page/contact">Contact us →</a>',
                    'Ingin mendukung atau bermitra dengan kami? <a href="internal://page/donasi">Donasi →</a> · <a href="internal://page/kontak">Hubungi kami →</a>'
                )],
            ],

            'faq' => [
                ['h1', $m('Frequently asked questions', 'Pertanyaan yang sering diajukan')],
                ['p', $m(
                    'Answers to the questions we hear most often. If yours is not here, <a href="internal://page/contact">contact us</a>.',
                    'Jawaban atas pertanyaan yang paling sering kami terima. Bila pertanyaan Anda belum ada, <a href="internal://page/kontak">hubungi kami</a>.'
                )],
                ['h2', $m('Donations', 'Donasi')],
                ['accordion', ['items' => [
                    [$m('How can I tell an official donation request from a fake one?', 'Bagaimana membedakan permintaan donasi resmi dari yang palsu?'),
                        $m('We do not ask for donations through personal numbers. Official donation details are only on the Donate page of this website.', 'Kami tidak meminta donasi melalui nomor pribadi. Rincian donasi resmi hanya ada di halaman Donasi situs ini.')],
                    [$m('Is my donation safe?', 'Apakah donasi saya aman?'), $todo('Answer: how donations are received, recorded and used.', 'Jawaban: bagaimana donasi diterima, dicatat, dan digunakan.')],
                    [$m('When and how will donations be reported?', 'Kapan dan bagaimana donasi dilaporkan?'), $todo('Answer: reporting schedule and where reports are published.', 'Jawaban: jadwal pelaporan dan tempat laporan diterbitkan.')],
                    [$m('Can I choose which program my donation supports?', 'Apakah saya bisa memilih program yang didukung donasi saya?'), $todo('Answer.', 'Jawaban.')],
                    [$m('Can I cancel a monthly donation?', 'Bagaimana cara membatalkan donasi bulanan?'), $todo('Answer, or remove this question if monthly donations are not offered.', 'Jawaban, atau hapus pertanyaan ini bila donasi bulanan tidak ditawarkan.')],
                    [$m('Can I visit the program sites?', 'Apakah saya bisa berkunjung ke lokasi program?'), $todo('Answer.', 'Jawaban.')],
                ]]],
                ['h2', $m('Our programs', 'Program kami')],
                ['accordion', ['items' => [
                    [$m('Where does YSBH work?', 'Di mana YSBH bekerja?'),
                        $m('YSBH works in Papua, Highland Papua and Central Papua provinces, especially in remote areas with limited access to health services.', 'YSBH bekerja di Provinsi Papua, Papua Pegunungan, dan Papua Tengah, terutama di daerah terpencil yang akses layanan kesehatannya terbatas.')],
                    [$m('Which health programs do you run?', 'Program kesehatan apa saja yang dijalankan?'),
                        $m('Malaria, immunization, maternal and child health, tuberculosis (TB) and HIV. See the Our Programs page for details.', 'Malaria, imunisasi, kesehatan ibu dan anak, tuberkulosis (TBC), dan HIV. Lihat halaman Program Kami untuk rinciannya.')],
                ]]],
                ['h2', $m('Transparency and trust', 'Transparansi dan kepercayaan')],
                ['accordion', ['items' => [
                    [$m('Is YSBH a registered foundation?', 'Apakah YSBH yayasan yang terdaftar?'),
                        $m('Yes. YSBH was established as a legal foundation in 2014. Our legal details are on the Credibility page.', 'Ya. YSBH berdiri sebagai yayasan berbadan hukum pada tahun 2014. Data hukum kami ada di halaman Kredibilitas.')],
                    [$m('Where can I read your reports?', 'Di mana saya bisa membaca laporan kalian?'), $todo('Answer: where annual and financial reports are published.', 'Jawaban: tempat laporan tahunan dan keuangan diterbitkan.')],
                ]]],
                ['h2', $m('Working with us', 'Bekerja sama dengan kami')],
                ['accordion', ['items' => [
                    [$m('How can my organization partner with YSBH?', 'Bagaimana organisasi saya dapat bermitra dengan YSBH?'), $todo('Answer: who to contact and what we need from partners.', 'Jawaban: siapa yang dihubungi dan apa yang kami perlukan dari mitra.')],
                    [$m('How can I contact YSBH?', 'Bagaimana cara menghubungi YSBH?'), $m('Use the details on the Contact page.', 'Gunakan kontak yang tercantum di halaman Hubungi Kami.')],
                ]]],
            ],

            'research' => [
                ['h1', $m('Research', 'Penelitian')],
                ['p', $m(
                    'YSBH develops health research to support better programs and policies in Papua. Here you can read our latest research articles and publications.',
                    'YSBH mengembangkan penelitian kesehatan untuk mendukung program dan kebijakan yang lebih baik di Papua. Di sini Anda dapat membaca artikel penelitian dan publikasi terbaru kami.'
                )],
                ['h2', $m('Research focus', 'Fokus penelitian')],
                ['p', $todo('Describe three to five research themes (for example public health research programs).', 'Uraikan tiga sampai lima tema penelitian (mis. program penelitian kesehatan masyarakat).')],
                ['h2', $m('Latest research', 'Penelitian terbaru')],
                ['p', $todo('In the block below, choose the category "Penelitian" in the Properties panel (create the category first under Categories). Until a category is chosen the block shows no articles.', 'Pada blok di bawah, pilih kategori "Penelitian" di panel Properti (buat kategorinya lebih dulu di menu Kategori). Selama belum ada kategori yang dipilih, blok tidak menampilkan artikel.')],
                ['latest', [
                    'source' => 'categories', 'categories' => [], 'featured_count' => '3', 'pinned' => [], 'mirror' => false,
                    'list_layout' => 'cols', 'columns' => '3', 'paging' => 'pages', 'limit' => '6', 'show_filter' => false,
                    'show_image' => true, 'show_category' => true, 'show_date' => true, 'show_excerpt' => true,
                ]],
                ['h2', $m('Publications and downloads', 'Publikasi dan unduhan')],
                ['p', $todo('Add papers and reports (title, authors, year) with the Downloads block or links.', 'Tambahkan makalah dan laporan (judul, penulis, tahun) dengan blok Unduhan atau tautan.')],
                ['h2', $m('Research partnerships', 'Kemitraan penelitian')],
                ['p', $m(
                    'Interested in collaborating on research? <a href="internal://page/contact">Contact us →</a>',
                    'Tertarik bekerja sama dalam penelitian? <a href="internal://page/kontak">Hubungi kami →</a>'
                )],
            ],
        ];
    }
}
