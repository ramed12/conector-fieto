<?php

use Illuminate\Database\Seeder;
use App\Model\Users;
use App\Model\Cities;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

    	//Adiciona o usuário padrão
        $user = Users::where('email', 'fagner.sa12@hotmail.com')->first();

        if(empty($user)) {
        	Users::create(array(
				'first_name'        => 'Fagner',
				'last_name'         => 'Alves',
				'email'             => 'fagner.sa12@hotmail.com',
				'password'          => '@123',
				'status'            => true
        	));
        }

        //Adiciona as cidades na tabela
        $cities = DB::table('cities')->first();

        if(empty($cities))
        {
            $cities = file_get_contents(__DIR__."/files/cities_ceps.json");            
            $data   = json_decode($cities, true);

            foreach ($data as $key => $row) {
              Cities::create(array(
                "name"      => $row['name'],
                "status"    =>  $row['status'],
                "states_id" => $row['states_id'],
                "cd_ibge"   =>  $row['cd_ibge'],
                "start_cep" =>  $row['start_cep'],
                "end_cep"   =>  $row['end_cep']
              ));
            }            
        }   

    }
}
