<?php

namespace Controllers;

use Models\User;

class UserController {
    public $db;
    public $data;
    public $user;

    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->user = new User($this->db);
    }

    public function handleRequest()
    {
        if(isset($this->data->user->id)) {
            $this->user->id = $this->data->user->id;
        }
        if(isset($this->data->user->name)) {
            $this->user->name = $this->data->user->name;
        }
        if(isset($this->data->user->email)) {
            $this->user->email = $this->data->user->email;
        }
        if(isset($this->data->user->pass)) {
            $this->user->pass = $this->data->user->pass;
        }
        if(isset($this->data->user->status)) {
            $this->user->status = $this->data->user->status;
        }
        if(isset($this->data->user->city)) {
            $this->user->city = $this->data->user->city;
        }
        if(isset($this->data->user->address)) {
            $this->user->address = $this->data->user->address;
        }
        if(isset($this->data->user->phone)) {
            $this->user->phone = $this->data->user->phone;
        }

        $request = $_SERVER['REQUEST_METHOD'];

        switch($request) {
            case 'GET':
                if(isset($this->data->user)) {
                    if(isset($this->data->all) && !empty($this->data->all)) {
                        $this->user->getAll();
                    }
                    if(isset($this->data->byID) && !empty($this->data->byID)) {
                        $this->user->getByID();
                    }
                    if(isset($this->data->byEmail) && !empty($this->data->byEmail)) {
                        $this->user->getByEmail();
                    }
                    if(isset($this->data->byName) && !empty($this->data->byName)) {
                        $this->user->getByName();
                    }
                    if(isset($this->data->byCity) && !empty($this->data->byCity)) {
                        $this->user->getByCity();
                    }
                } else {
                    echo json_encode(["user" => 'Nije pronađen korisnik.'], JSON_PRETTY_PRINT);
                    exit();
                }
                break;
            case 'POST':
                if(isset($this->data->signin) && !empty($this->data->signin)) {
                    if(!empty($this->user->name) && !empty($this->user->email) && !empty($this->user->pass) 
                    && !empty($this->user->address) && !empty($this->user->city) && !empty($this->user->phone)) {
                        $this->user->create();
                    } else
                    echo json_encode(['user' => 'Nije moguće kreirati korisnika, molimo Vas da unesete sve podatke!']);
                }
                break;
            case 'PUT':
                if(isset($this->data->cities->id)) {
                    
                }
                break;
            case 'DELETE':
                if(isset($this->data->cities->id)) {
                    
                }
                break;
        }    
    }
}

?>