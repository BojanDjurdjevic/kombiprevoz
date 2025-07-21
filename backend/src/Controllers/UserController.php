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
        if(isset($this->data->users->id)) {
            $this->user->id = $this->data->users->id;
        }
        if(isset($this->data->users->name)) {
            $this->user->name = $this->data->users->name;
        }
        if(isset($this->data->users->email)) {
            $this->user->email = $this->data->users->email;
        }
        if(isset($this->data->users->pass)) {
            $this->user->pass = $this->data->users->pass;
        }
        if(isset($this->data->users->remember)) {
            $this->user->remember = $this->data->users->remember;
        }
        if(isset($this->data->new_pass->password)) {
            $this->user->new_pass = $this->data->new_pass->password;
        }
        if(isset($this->data->new_pass->password)) {
            $this->user->new_pass_confirm = $this->data->new_pass->confirmation_pass;
        }
        if(isset($this->data->users->status)) {
            $this->user->status = $this->data->users->status;
        }
        if(isset($this->data->users->city)) {
            $this->user->city = $this->data->users->city;
        }
        if(isset($this->data->users->address)) {
            $this->user->address = $this->data->users->address;
        }
        if(isset($this->data->users->phone)) {
            $this->user->phone = $this->data->users->phone;
        }
        if(isset($this->data->token)) {
            $this->user->token = $this->data->token;
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
                    if(isset($this->data->token) && !empty($this->data->token)) $this->user->checkToken($this->user->token);
                } else {
                    echo json_encode(["user" => 'Nije pronađen korisnik.'], JSON_PRETTY_PRINT);
                    exit();
                }
                break;
            case 'POST':
                if(isset($this->data->users->signin) && !empty($this->data->users->signin)) {
                    if(!empty($this->user->name) && !empty($this->user->email) && !empty($this->user->pass) 
                    && !empty($this->user->address) && !empty($this->user->city) && !empty($this->user->phone)) {
                        $this->user->create();
                    } else {
                        http_response_code(422);
                        echo json_encode(['error' => 'Nije moguće kreirati korisnika, molimo Vas da unesete sve podatke!']);
                    }
                    
                }
                if(isset($this->data->byAdmin) && !empty($this->data->byAdmin)) {
                    if(!empty($this->user->name) && !empty($this->user->email) && !empty($this->user->pass) 
                    && !empty($this->user->address) && !empty($this->user->city) && !empty($this->user->phone) && !empty($this->user->status)) {
                        if(Validator::isSuper() || Validator::isAdmin()) $this->user->createByAdmin();
                        else echo json_encode(['user' => 'Niste autorizovani da kreirate korisnike!']);
                    } else
                    echo json_encode(['error' => 'Nije moguće kreirati korisnika, molimo Vas da unesete sve podatke!']);
                }
                if(isset($this->data->users->login) && !empty($this->data->users->login)) {
                    if(!empty($this->user->email) && !empty($this->user->pass)) {
                        $this->user->login();
                    } else
                    echo json_encode(['error' => 'Nije moguće ulogovati se, molimo Vas da unesete sve podatke!']);
                }
                if(isset($this->data->logout) && !empty($this->data->logout)) {
                    $this->user->logout();
                }
                
                break;
            case 'PUT':
                if(isset($this->data->updateProfile) && !empty($this->data->updateProfile)) {
                    if($this->user->isOwner() || Validator::isAdmin() || Validator::isSuper()) $this->user->update();
                    else echo json_encode(['user' => 'Niste autorizovani da vršite izmene!']);
                }
                if(isset($this->data->updatePass) && !empty($this->data->updatePass)) {
                    if($this->user->isOwner()) $this->user->updatePassword();
                    else {
                        http_response_code(401);
                        echo json_encode(['error' => 'Niste autorizovani da vršite izmene!']);
                    } 
                }
                if(isset($this->data->resetPass) && !empty($this->data->resetPass)) {
                    $this->user->resetPassword();
                }
                if(isset($this->data->token) && !empty($this->data->token)) {
                    $this->user->processResetPassword();
                }
                if(isset($this->data->role) && !empty($this->data->role)) {
                    if(Validator::isSuper()) $this->user->changeRole();
                    else echo json_encode(['user' => 'Niste autorizovani da vršite izmene!']);
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