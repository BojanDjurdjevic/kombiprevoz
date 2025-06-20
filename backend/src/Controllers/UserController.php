<?php

namespace Controllers;

use Models\User;
use Rules\Validator;

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
        if(isset($this->data->new_pass->password)) {
            $this->user->new_pass = $this->data->new_pass->password;
        }
        if(isset($this->data->new_pass->password)) {
            $this->user->new_pass_confirm = $this->data->new_pass->confirmation_pass;
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
                if(isset($this->data->user) && !empty($this->data->user)) {
                    if(isset($this->data->all) && !empty($this->data->all)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getAll();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
                    }
                    if(isset($this->data->byID) && !empty($this->data->byID)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getByID();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
                    }
                    if(isset($this->data->byEmail) && !empty($this->data->byEmail)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getByEmail();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
                    }
                    if(isset($this->data->byName) && !empty($this->data->byName)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getByName();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
                    }
                    if(isset($this->data->byCity) && !empty($this->data->byCity)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getByCity();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
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
                if(isset($this->data->login) && !empty($this->data->login)) {
                    if(!empty($this->user->email) && !empty($this->user->pass)) {
                        $this->user->login();
                    } else
                    echo json_encode(['user' => 'Nije moguće ulogovati se, molimo Vas da unesete sve podatke!']);
                }
                if(isset($this->data->logout) && !empty($this->data->logout)) {
                    $this->user->logout();
                }
                break;
            case 'PUT':
                if($this->user->isOwner() || Validator::isAdmin() || Validator::isSuper()) {
                    if(isset($this->data->updateProfile) && !empty($this->data->updateProfile)) {
                        $this->user->update();
                    }
                    if(isset($this->data->updatePass) && !empty($this->data->updatePass)) {
                        if($this->user->isOwner()) $this->user->updatePassword();
                        else echo json_encode(['user' => 'Niste autorizovani da vršite izmene!']);
                    }
                }
                break;
            case 'DELETE':
                if(isset($this->data->delete) && !empty($this->data->delete)) {
                    if($this->user->isOwner() || Validator::isAdmin() || Validator::isSuper()) {
                        $this->user->delete();
                    }
                }
                if(isset($this->data->restore) && !empty($this->data->restore)) {
                    if(Validator::isAdmin() || Validator::isSuper()) {
                        $this->user->restore();
                    }
                }
                break;
        }    
    }
}

?>