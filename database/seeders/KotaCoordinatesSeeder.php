<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk mengisi koordinat realistis kota-kota dan provinsi di Indonesia.
 * Data koordinat berdasarkan pemetaan resmi ibukota kabupaten/kota.
 *
 * Cara kerja:
 * - Match nama_kota / nama_provinsi di database dengan lookup table
 * - Update latitude & longitude yang masih null
 * - Tidak menimpa data yang sudah diisi manual
 */
class KotaCoordinatesSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════
        //  Provinsi Coordinates
        // ══════════════════════════════════
        $provinsiCoords = [
            'Aceh' => [-5.5483, 95.3238],
            'Sumatera Utara' => [2.1154, 99.5451],
            'Sumatera Barat' => [-0.7399, 100.8000],
            'Riau' => [1.7354, 101.7068],
            'Jambi' => [-1.4852, 102.4381],
            'Sumatera Selatan' => [-3.3194, 104.9147],
            'Bengkulu' => [-3.5778, 102.3464],
            'Lampung' => [-4.5586, 105.4068],
            'Kepulauan Bangka Belitung' => [-2.4961, 106.4396],
            'Kepulauan Riau' => [3.9457, 108.1429],
            'DKI Jakarta' => [-6.2088, 106.8456],
            'Jawa Barat' => [-6.9175, 107.6191],
            'Jawa Tengah' => [-7.1510, 110.1403],
            'DI Yogyakarta' => [-7.7956, 110.3695],
            'Jawa Timur' => [-7.5361, 112.2384],
            'Banten' => [-6.4058, 106.0640],
            'Bali' => [-8.4095, 115.1889],
            'Nusa Tenggara Barat' => [-8.6529, 117.3616],
            'Nusa Tenggara Timur' => [-8.6574, 121.0794],
            'Kalimantan Barat' => [-0.2788, 111.4753],
            'Kalimantan Tengah' => [-1.6815, 113.3824],
            'Kalimantan Selatan' => [-3.0926, 115.2838],
            'Kalimantan Timur' => [1.6407, 116.4194],
            'Kalimantan Utara' => [3.0738, 116.0414],
            'Sulawesi Utara' => [0.6247, 123.9750],
            'Sulawesi Tengah' => [-1.4301, 121.4457],
            'Sulawesi Selatan' => [-3.6688, 119.9741],
            'Sulawesi Tenggara' => [-4.1449, 122.1749],
            'Gorontalo' => [0.6999, 122.4467],
            'Sulawesi Barat' => [-2.8442, 119.2321],
            'Maluku' => [-3.2385, 130.1453],
            'Maluku Utara' => [1.5709, 127.8088],
            'Papua' => [-4.2699, 138.0804],
            'Papua Barat' => [-1.3361, 133.1747],
        ];

        foreach ($provinsiCoords as $nama => $coords) {
            DB::table('provinsi')
                ->where('nama_provinsi', 'like', "%{$nama}%")
                ->whereNull('latitude')
                ->update([
                    'latitude' => $coords[0],
                    'longitude' => $coords[1],
                ]);
        }

        // ══════════════════════════════════
        //  Kota/Kabupaten Coordinates
        //  (Major cities & capitals)
        // ══════════════════════════════════
        $kotaCoords = [
            // DKI Jakarta
            'Jakarta Pusat' => [-6.1862, 106.8340],
            'Jakarta Utara' => [-6.1384, 106.8638],
            'Jakarta Barat' => [-6.1482, 106.7379],
            'Jakarta Selatan' => [-6.2615, 106.8106],
            'Jakarta Timur' => [-6.2250, 106.9005],

            // Jawa Barat
            'Bandung' => [-6.9175, 107.6191],
            'Bogor' => [-6.5944, 106.7892],
            'Depok' => [-6.4025, 106.7942],
            'Bekasi' => [-6.2383, 107.0000],
            'Cirebon' => [-6.7320, 108.5527],
            'Sukabumi' => [-6.9219, 106.9273],
            'Tasikmalaya' => [-7.3274, 108.2207],
            'Karawang' => [-6.3227, 107.3376],
            'Garut' => [-7.2167, 107.9000],
            'Subang' => [-6.5707, 107.7527],
            'Cianjur' => [-6.8184, 107.1424],
            'Purwakarta' => [-6.5534, 107.4404],
            'Sumedang' => [-6.8556, 107.9215],
            'Majalengka' => [-6.8363, 108.2280],
            'Indramayu' => [-6.3276, 108.3254],
            'Kuningan' => [-6.9756, 108.4836],
            'Ciamis' => [-7.3326, 108.3521],
            'Pangandaran' => [-7.6831, 108.6562],
            'Bandung Barat' => [-6.8414, 107.4689],

            // Jawa Tengah
            'Semarang' => [-6.9666, 110.4196],
            'Solo' => [-7.5755, 110.8243],
            'Surakarta' => [-7.5755, 110.8243],
            'Magelang' => [-7.4798, 110.2177],
            'Pekalongan' => [-6.8885, 109.6753],
            'Tegal' => [-6.8797, 109.1428],
            'Purwokerto' => [-7.4244, 109.2384],
            'Banyumas' => [-7.5153, 109.2945],
            'Cilacap' => [-7.7268, 109.0154],
            'Kebumen' => [-7.6720, 109.6522],
            'Purworejo' => [-7.7186, 110.0010],
            'Klaten' => [-7.7059, 110.5958],
            'Boyolali' => [-7.5314, 110.5967],
            'Sragen' => [-7.4316, 111.0175],
            'Karanganyar' => [-7.6042, 110.9581],
            'Wonogiri' => [-7.8067, 110.9242],
            'Kudus' => [-6.8049, 110.8394],
            'Jepara' => [-6.5912, 110.6738],
            'Demak' => [-6.8937, 110.6380],
            'Kendal' => [-7.0279, 110.2018],
            'Temanggung' => [-7.3170, 110.1747],
            'Wonosobo' => [-7.3608, 109.9029],
            'Brebes' => [-6.8724, 109.0404],
            'Pemalang' => [-6.8831, 109.3824],
            'Batang' => [-6.8959, 109.7229],
            'Blora' => [-6.8663, 111.4107],
            'Rembang' => [-6.7054, 111.3458],
            'Pati' => [-6.7546, 111.0377],
            'Grobogan' => [-7.0244, 110.8919],
            'Salatiga' => [-7.3306, 110.5084],

            // Jawa Timur
            'Surabaya' => [-7.2575, 112.7521],
            'Malang' => [-7.9666, 112.6326],
            'Sidoarjo' => [-7.4478, 112.7183],
            'Gresik' => [-7.1587, 112.6530],
            'Pasuruan' => [-7.6469, 112.9075],
            'Mojokerto' => [-7.4704, 112.4402],
            'Probolinggo' => [-7.7543, 113.2159],
            'Batu' => [-7.8671, 112.5232],
            'Kediri' => [-7.8164, 112.0116],
            'Blitar' => [-8.1015, 112.1615],
            'Tulungagung' => [-8.0654, 111.9025],
            'Trenggalek' => [-8.0473, 111.7087],
            'Nganjuk' => [-7.6054, 111.9041],
            'Madiun' => [-7.6299, 111.5240],
            'Magetan' => [-7.6471, 111.3582],
            'Ponorogo' => [-7.8653, 111.4601],
            'Pacitan' => [-8.1935, 111.1047],
            'Jember' => [-8.1845, 113.6681],
            'Banyuwangi' => [-8.2193, 114.3691],
            'Bondowoso' => [-7.9167, 113.8215],
            'Situbondo' => [-7.7059, 114.0000],
            'Lumajang' => [-8.1333, 113.2242],
            'Lamongan' => [-7.1222, 112.4167],
            'Tuban' => [-6.8987, 112.0496],
            'Bojonegoro' => [-7.1507, 111.8811],
            'Jombang' => [-7.5459, 112.2319],
            'Bangkalan' => [-7.0483, 112.7335],
            'Sampang' => [-7.1839, 113.2464],
            'Pamekasan' => [-7.1601, 113.4741],
            'Sumenep' => [-7.0167, 113.8668],
            'Ngawi' => [-7.4035, 111.4502],

            // DI Yogyakarta
            'Yogyakarta' => [-7.7956, 110.3695],
            'Sleman' => [-7.7159, 110.3557],
            'Bantul' => [-7.8880, 110.3266],
            'Kulon Progo' => [-7.8262, 110.1640],
            'Gunung Kidul' => [-7.9870, 110.6019],
            'Gunungkidul' => [-7.9870, 110.6019],

            // Banten
            'Tangerang' => [-6.1784, 106.6319],
            'Tangerang Selatan' => [-6.2843, 106.7105],
            'Serang' => [-6.1182, 106.1504],
            'Cilegon' => [-6.0023, 106.0528],
            'Pandeglang' => [-6.3089, 106.1047],
            'Lebak' => [-6.5625, 106.2521],

            // Bali
            'Denpasar' => [-8.6705, 115.2126],
            'Badung' => [-8.5819, 115.1771],
            'Gianyar' => [-8.5350, 115.3147],
            'Tabanan' => [-8.5415, 115.1253],
            'Klungkung' => [-8.5361, 115.4058],
            'Bangli' => [-8.4544, 115.3553],
            'Karangasem' => [-8.4474, 115.6062],
            'Buleleng' => [-8.1116, 115.0889],
            'Jembrana' => [-8.3580, 114.6346],

            // Sumatera Utara
            'Medan' => [3.5952, 98.6722],
            'Pematang Siantar' => [2.9516, 99.0504],
            'Binjai' => [3.6003, 98.4965],
            'Tebing Tinggi' => [3.3276, 99.1625],
            'Tanjung Balai' => [2.9622, 99.7992],
            'Padang Sidempuan' => [1.3787, 99.2733],
            'Deli Serdang' => [3.5042, 98.9377],
            'Langkat' => [3.7704, 98.2756],
            'Karo' => [3.1042, 98.3919],
            'Simalungun' => [2.8620, 99.0736],
            'Asahan' => [2.8680, 99.7006],

            // Sumatera Barat
            'Padang' => [-0.9471, 100.4172],
            'Bukittinggi' => [-0.3052, 100.3691],
            'Payakumbuh' => [-0.2214, 100.6299],
            'Pariaman' => [-0.6226, 100.1182],
            'Solok' => [-0.7997, 100.6537],
            'Sawahlunto' => [-0.6816, 100.7764],

            // Riau
            'Pekanbaru' => [0.5071, 101.4478],
            'Dumai' => [1.6889, 101.4500],
            'Bengkalis' => [1.4838, 102.0851],
            'Kampar' => [0.3131, 101.1478],
            'Indragiri Hilir' => [-0.3365, 103.1012],
            'Indragiri Hulu' => [-0.5417, 102.1311],
            'Rokan Hilir' => [2.1000, 101.5000],
            'Rokan Hulu' => [0.9500, 100.6000],
            'Siak' => [1.1049, 102.1479],
            'Kuantan Singingi' => [-0.5438, 101.5200],

            // Jambi
            'Jambi' => [-1.6101, 103.6131],
            'Muaro Jambi' => [-1.5430, 103.8585],
            'Bungo' => [-1.5083, 102.1000],
            'Tebo' => [-1.4333, 102.3667],
            'Merangin' => [-2.0833, 102.1500],
            'Kerinci' => [-1.9500, 101.5000],
            'Batang Hari' => [-1.6333, 103.3000],
            'Tanjung Jabung Barat' => [-1.2167, 103.6167],
            'Tanjung Jabung Timur' => [-1.0667, 104.1167],
            'Sungai Penuh' => [-2.0586, 101.3973],

            // Sumatera Selatan
            'Palembang' => [-2.9761, 104.7754],
            'Prabumulih' => [-3.4286, 104.2306],
            'Lubuk Linggau' => [-3.2873, 102.8605],
            'Pagar Alam' => [-4.0214, 103.2468],

            // Bengkulu
            'Bengkulu' => [-3.7928, 102.2608],

            // Lampung
            'Bandar Lampung' => [-5.3971, 105.2668],
            'Metro' => [-5.1139, 105.3068],

            // Kepulauan Bangka Belitung
            'Pangkal Pinang' => [-2.1316, 106.1169],

            // Kepulauan Riau
            'Batam' => [1.0456, 104.0305],
            'Tanjung Pinang' => [0.9182, 104.4460],

            // NTB
            'Mataram' => [-8.5833, 116.1167],
            'Bima' => [-8.4600, 118.7267],
            'Lombok Barat' => [-8.5750, 116.0833],
            'Lombok Tengah' => [-8.7167, 116.2500],
            'Lombok Timur' => [-8.5500, 116.5167],
            'Sumbawa' => [-8.4833, 117.4167],
            'Sumbawa Barat' => [-8.7833, 116.9500],
            'Dompu' => [-8.5333, 118.4667],
            'Lombok Utara' => [-8.4000, 116.2667],

            // NTT
            'Kupang' => [-10.1772, 123.6070],
            'Ende' => [-8.8422, 121.6496],
            'Maumere' => [-8.6194, 122.2121],
            'Ruteng' => [-8.6134, 120.4734],

            // Kalimantan Barat
            'Pontianak' => [-0.0263, 109.3425],
            'Singkawang' => [0.9054, 108.9872],
            'Sambas' => [1.3583, 109.3000],
            'Ketapang' => [-1.8293, 109.9787],

            // Kalimantan Tengah
            'Palangkaraya' => [-2.2136, 113.9108],
            'Pangkalan Bun' => [-2.6843, 111.6179],
            'Sampit' => [-2.5325, 112.9514],
            'Muara Teweh' => [-0.9500, 114.9000],

            // Kalimantan Selatan
            'Banjarmasin' => [-3.3194, 114.5908],
            'Banjarbaru' => [-3.4415, 114.8430],
            'Barabai' => [-2.5833, 115.3833],
            'Martapura' => [-3.4167, 114.8500],
            'Tanah Bumbu' => [-3.5500, 115.9167],

            // Kalimantan Timur
            'Samarinda' => [-0.4948, 117.1436],
            'Balikpapan' => [-1.2379, 116.8529],
            'Bontang' => [0.1333, 117.5000],
            'Kutai Kartanegara' => [-0.5000, 116.9667],
            'Berau' => [2.1583, 117.4890],
            'Paser' => [-1.8500, 116.1000],
            'Penajam Paser Utara' => [-1.2000, 116.6000],

            // Kalimantan Utara
            'Tanjung Selor' => [2.8500, 117.3500],
            'Tarakan' => [3.3267, 117.6000],
            'Nunukan' => [4.1396, 117.6655],
            'Malinau' => [3.5833, 116.6333],
            'Bulungan' => [3.0000, 117.1000],

            // Sulawesi Utara
            'Manado' => [1.4748, 124.8421],
            'Bitung' => [1.4414, 125.1967],
            'Tomohon' => [1.3213, 124.8490],
            'Kotamobagu' => [0.7333, 124.3167],
            'Minahasa' => [1.2833, 124.9667],

            // Sulawesi Tengah
            'Palu' => [-0.9003, 119.8779],
            'Poso' => [-1.3953, 120.7536],
            'Luwuk' => [-0.9476, 122.7903],
            'Tolitoli' => [1.0500, 120.7833],
            'Donggala' => [-0.6791, 119.7403],

            // Sulawesi Selatan
            'Makassar' => [-5.1477, 119.4327],
            'Parepare' => [-4.0135, 119.6255],
            'Palopo' => [-2.9928, 120.1968],
            'Maros' => [-5.0000, 119.5667],
            'Gowa' => [-5.3167, 119.4500],
            'Bone' => [-4.5000, 120.0000],
            'Wajo' => [-4.0500, 120.0500],
            'Bulukumba' => [-5.5500, 120.2000],
            'Pinrang' => [-3.7833, 119.6500],
            'Enrekang' => [-3.5667, 119.8167],
            'Tana Toraja' => [-3.0667, 119.8167],
            'Luwu' => [-3.2333, 120.3167],
            'Sinjai' => [-5.1333, 120.2500],
            'Bantaeng' => [-5.5333, 119.9833],
            'Jeneponto' => [-5.6500, 119.7500],
            'Takalar' => [-5.4167, 119.4833],
            'Soppeng' => [-4.3333, 120.0833],
            'Barru' => [-4.4000, 119.6333],
            'Pangkajene Kepulauan' => [-4.7333, 119.5333],
            'Selayar' => [-6.1167, 120.5000],
            'Toraja Utara' => [-2.9333, 119.8833],
            'Luwu Utara' => [-2.5500, 120.3500],
            'Luwu Timur' => [-2.5833, 121.1333],
            'Sidenreng Rappang' => [-3.9833, 119.9667],
            'Kepulauan Selayar' => [-6.1167, 120.5000],

            // Sulawesi Tenggara
            'Kendari' => [-3.9985, 122.5130],
            'Bau-Bau' => [-5.4657, 122.6285],
            'Konawe' => [-3.7667, 122.0667],
            'Muna' => [-4.9333, 122.6333],
            'Buton' => [-5.3667, 122.9667],
            'Kolaka' => [-4.0667, 121.6000],
            'Wakatobi' => [-5.3167, 123.5833],
            'Bombana' => [-4.6833, 121.8667],
            'Konawe Selatan' => [-4.2500, 122.4167],

            // Gorontalo
            'Gorontalo' => [0.5435, 123.0568],
            'Gorontalo Utara' => [0.8833, 122.3667],
            'Boalemo' => [0.5667, 122.2000],
            'Pohuwato' => [0.6500, 121.6167],
            'Bone Bolango' => [0.5333, 123.2500],

            // Sulawesi Barat
            'Mamuju' => [-2.6796, 118.8850],
            'Mamuju Utara' => [-1.6667, 119.3667],
            'Polewali Mandar' => [-3.4000, 119.3333],
            'Majene' => [-3.5333, 118.9667],
            'Mamasa' => [-2.9167, 119.3500],

            // Maluku
            'Ambon' => [-3.6554, 128.1903],
            'Tual' => [-5.6333, 132.7333],
            'Maluku Tengah' => [-3.3167, 129.5000],
            'Maluku Tenggara' => [-5.7500, 132.7333],
            'Buru' => [-3.3833, 126.7000],
            'Seram Bagian Barat' => [-2.9833, 128.3833],
            'Seram Bagian Timur' => [-3.2833, 130.9833],
            'Kepulauan Aru' => [-6.0000, 134.2500],

            // Maluku Utara
            'Ternate' => [0.7714, 127.3770],
            'Tidore Kepulauan' => [0.6917, 127.4000],
            'Halmahera Utara' => [1.8833, 127.9833],
            'Halmahera Selatan' => [-0.5333, 127.8667],
            'Halmahera Tengah' => [0.5333, 128.2500],
            'Halmahera Barat' => [1.0333, 127.4333],
            'Halmahera Timur' => [1.1000, 128.2333],
            'Kepulauan Sula' => [-1.8667, 125.3500],
            'Pulau Morotai' => [2.3333, 128.4167],

            // Papua
            'Jayapura' => [-2.5916, 140.6690],
            'Merauke' => [-8.4932, 140.4018],
            'Nabire' => [-3.3668, 135.4964],
            'Timika' => [-4.5475, 136.8877],
            'Biak' => [-1.0381, 136.0480],
            'Manokwari' => [-0.8614, 134.0620],
            'Sorong' => [-0.8762, 131.2550],
            'Fakfak' => [-2.9267, 132.2958],

            // Papua Barat
            // (Manokwari and Sorong exist in Papua section already)

            // Aceh
            'Banda Aceh' => [5.5483, 95.3238],
            'Lhokseumawe' => [5.1798, 97.1487],
            'Langsa' => [4.4683, 97.9661],
            'Sabang' => [5.8949, 95.3165],
            'Subulussalam' => [2.6424, 98.0254],
            'Aceh Besar' => [5.3737, 95.4927],
            'Pidie' => [5.0680, 96.0930],
            'Aceh Utara' => [5.0833, 97.1417],
            'Aceh Timur' => [4.5500, 97.7333],
            'Aceh Selatan' => [3.1667, 97.4833],
            'Aceh Barat' => [4.4667, 96.1500],
            'Aceh Tengah' => [4.6000, 96.8500],
            'Bireuen' => [5.2017, 96.7009],
            'Aceh Tamiang' => [4.2500, 97.9667],
            'Nagan Raya' => [4.1500, 96.5333],
            'Aceh Barat Daya' => [3.8000, 97.1000],
            'Gayo Lues' => [3.9500, 97.4000],
            'Aceh Tenggara' => [3.3333, 97.7167],
            'Simeulue' => [2.6167, 96.0833],
            'Bener Meriah' => [4.7167, 96.8500],
            'Pidie Jaya' => [5.1500, 96.3500],
            'Aceh Singkil' => [2.3500, 97.7833],
            'Aceh Jaya' => [4.9333, 95.6500],

            // Kraksaan / Probolinggo area (school location)
            'Kraksaan' => [-7.7589, 113.3958],
        ];

        // Batch update — match kota by name
        foreach ($kotaCoords as $nama => $coords) {
            DB::table('kota')
                ->where('nama_kota', 'like', "%{$nama}%")
                ->whereNull('latitude')
                ->update([
                    'latitude' => $coords[0],
                    'longitude' => $coords[1],
                ]);
        }

        $this->command->info(' Koordinat kota & provinsi berhasil di-seed.');
        $this->command->info('   Provinsi: ' . count($provinsiCoords) . ' entries');
        $this->command->info('   Kota: ' . count($kotaCoords) . ' entries');
    }
}
