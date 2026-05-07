<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $provinceMap = DB::table('provinces')->pluck('id', 'name');

        $districts = [
            'Maputo Cidade' => ['KaMpfumo', 'Nlhamankulu', 'KaMaxakeni', 'KaMavota', 'KaMubukwana', 'KaTembe', 'KaNyaka'],
            'Maputo Província' => ['Matola', 'Boane', 'Marracuene', 'Namaacha', 'Moamba', 'Manhiça', 'Magude', 'Matutuíne'],
            'Gaza' => ['Xai-Xai', 'Chókwè', 'Chibuto', 'Mandlakazi', 'Bilene', 'Guijá', 'Mabalane', 'Chicualacuala', 'Chigubo', 'Massangena', 'Massingir', 'Limpopo', 'Mapai'],
            'Inhambane' => ['Inhambane', 'Maxixe', 'Vilankulo', 'Massinga', 'Morrumbene', 'Homoine', 'Inharrime', 'Zavala', 'Jangamo', 'Funhalouro', 'Panda', 'Mabote', 'Govuro'],
            'Manica' => ['Chimoio', 'Manica', 'Gondola', 'Sussundenga', 'Bárue', 'Mossurize', 'Macossa', 'Tambara', 'Machaze', 'Guro', 'Macate', 'Vanduzi'],
            'Sofala' => ['Beira', 'Dondo', 'Nhamatanda', 'Búzi', 'Gorongosa', 'Marromeu', 'Caia', 'Chemba', 'Cheringoma', 'Machanga', 'Muanza', 'Chibabava', 'Maríngue'],
            'Tete' => ['Tete', 'Moatize', 'Angónia', 'Changara', 'Cahora-Bassa', 'Mutarara', 'Tsangano', 'Chiuta', 'Marávia', 'Macanga', 'Chifunde', 'Dôa', 'Zumbo', 'Marara'],
            'Zambézia' => ['Quelimane', 'Alto Molócue', 'Chinde', 'Gilé', 'Gurué', 'Ile', 'Inhassunge', 'Lugela', 'Maganja da Costa', 'Milange', 'Mocuba', 'Mopeia', 'Morrumbala', 'Namacurra', 'Namarroi', 'Nicoadala', 'Pebane', 'Derre', 'Luabo', 'Mocubela', 'Molumbo', 'Mulevala'],
            'Nampula' => ['Nampula', 'Angoche', 'Eráti', 'Ilha de Moçambique', 'Lalaua', 'Malema', 'Meconta', 'Mecubúri', 'Memba', 'Mogincual', 'Mogovolas', 'Moma', 'Monapo', 'Mossuril', 'Muecate', 'Murrupula', 'Nacala-a-Velha', 'Nacala-Porto', 'Nacarôa', 'Rapale', 'Ribaué', 'Liúpo'],
            'Cabo Delgado' => ['Pemba', 'Chiúre', 'Montepuez', 'Mocímboa da Praia', 'Macomia', 'Mueda', 'Muidumbe', 'Nangade', 'Palma', 'Ancuabe', 'Balama', 'Mecúfi', 'Meluco', 'Namuno', 'Quissanga', 'Ibo', 'Metuge'],
            'Niassa' => ['Lichinga', 'Cuamba', 'Mandimba', 'Marrupa', 'Majune', 'Mecanhelas', 'Mecula', 'Metarica', 'Muembe', 'N\'gauma', 'Nipepe', 'Sanga', 'Chimbonila', 'Lago', 'Mavago'],
        ];

        foreach ($districts as $provinceName => $items) {
            $provinceId = $provinceMap[$provinceName] ?? null;
            if (!$provinceId) {
                continue;
            }

            foreach ($items as $districtName) {
                DB::table('districts')->updateOrInsert(
                    ['province_id' => $provinceId, 'name' => $districtName],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }
}
