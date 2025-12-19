<?php

namespace Controllers;

use Helpers\Logger;
use Models\User;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

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
                    
                    if(isset($this->data->byName) && !empty($this->data->byName)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getByName();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
                    }
                    if(isset($this->data->byCity) && !empty($this->data->byCity)) {
                        if(Validator::isAdmin() || Validator::isSuper()) $this->user->getByCity();
                        else echo json_encode(['user' => 'Niste autorizovani da vidite druge korisnike!']);
                    }
                    if(isset($this->data->token) && !empty($this->data->token)) $this->user->checkToken($this->user->token);
                }
                if(isset($this->data->users->byEmail) && !empty($this->data->users->byEmail)) {
                    if(Validator::isAdmin() || Validator::isSuper()) {
                        $result =  $this->user->getByEmail();

                        if ($result['success']) {
                            http_response_code(200);
                            echo json_encode([
                                'success' => true,
                                'user' => $result['user'],
                                'logs' => $result['logs']
                            ], JSON_UNESCAPED_UNICODE);
                            exit;

                        } else {
                            if ($result['error'] === 'invalid_email') {
                                http_response_code(400);
                            } elseif ($result['error'] === 'not_found') {
                                http_response_code(404);
                            } else {
                                http_response_code(500);
                            }

                            echo json_encode([
                                'error' => $result['message']
                            ], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    } 
                    else {
                        http_response_code(403);
                        echo json_encode(['error' => 'Niste autorizovani da vidite druge korisnike!']);
                        exit;
                    } 
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
                if(isset($this->data->users->byAdmin) && !empty($this->data->users->byAdmin)) {
                    if(!empty($this->user->name) && !empty($this->user->email) && !empty($this->user->address)
                     && !empty($this->user->city) && !empty($this->user->phone) && !empty($this->user->status)) {
                        if(Validator::isSuper() || Validator::isAdmin()) $this->user->createByAdmin();
                        else {
                            http_response_code(403);
                            echo json_encode([
                                'error' => 'Niste autorizovani da kreirate korisnike!'
                            ]);
                        }
                    } else {
                        http_response_code(422);
                        echo json_encode(['error' => 'Nije moguće kreirati korisnika, molimo Vas da unesete sve podatke!']);
                    }                 
                }
                if(isset($this->data->users->login) && !empty($this->data->users->login)) {
                    if(!empty($this->user->email) && !empty($this->user->pass)) {
                        $this->user->login();
                    } else {
                        http_response_code(403);
                        echo json_encode(['error' => 'Nije moguće ulogovati se, molimo Vas da unesete sve podatke!']);
                    }
                    
                }
                if(isset($this->data->logout) && !empty($this->data->logout)) {
                    $this->user->logout();
                }
                
                break;
            case 'PUT':
                if(isset($this->data->updateProfile) && !empty($this->data->updateProfile)) {
                    if($this->user->isOwner() || Validator::isAdmin() || Validator::isSuper()) $this->user->update();
                    else echo json_encode(['user' => 'Niste autorizovani da vršite izmene!']);
                    return;
                }
                if(isset($this->data->updatePass) && !empty($this->data->updatePass)) {
                    if($this->user->isOwner()) $this->user->updatePassword();
                    else {
                        http_response_code(401);
                        echo json_encode(['error' => 'Niste autorizovani da vršite izmene!']);
                    } 
                    return;
                }
                if(isset($this->data->resetPass) && !empty($this->data->resetPass)) {
                    $this->user->resetPassword();
                    return;
                }
                if(isset($this->data->token) && !empty($this->data->token)) {
                    $this->user->processResetPassword();
                    return;
                }
                if(isset($this->data->updateByAdmin) && !empty($this->data->updateByAdmin)) {
                    if(Validator::isSuper() || Validator::isAdmin()) $this->user->userUpdateByAdmin();
                    else {
                        http_response_code(403);
                        echo json_encode(['error' => 'Niste autorizovani da vršite izmene!']);
                        Logger::audit('Neautorizovani pokušaj promene statusa korisnika u userUpdateByAdmin()', $_SESSION['user']['id']);
                    } 
                    return;
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