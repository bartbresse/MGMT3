<?php

use Phalcon\Mvc\User\Component;

class StoreaclHandler extends Component
{
    private function storeAcl($idEigenaar, $idStructuurtable, $create, $read, $update, $delete, $id)
    {
        $acl = Acl::findFirst('Eigenaar_idEigenaar = ' . $idEigenaar . ' AND Structuurtable_idStructuurtable = ' . $idStructuurtable . ' AND Entityid = ' . $id);
        if (!$acl) {
            $acl = new Acl();
            $acl->Eigenaar_idEigenaar = $idEigenaar;
            $acl->Structuurtable_idStructuurtable = $idStructuurtable;
            $acl->Entityid = $id;
        }
        $acl->Create = $this->boolToNumber($create);
        $acl->Read = $this->boolToNumber($read);
        $acl->Update = $this->boolToNumber($update);
        $acl->Delete = $this->boolToNumber($delete);
        if (!$acl->save()) {
            print_r($acl->getMessages());
        }
    }

    private function boolToNumber($val)
    {
        if ($val == true) {
            return 1;
        } else {
            return 0;
        }
    }

    public function processAcl()
    {

    }

    public function newRule(EntityInfo $ei, $data, $eigenaar)
    {
        if ($eigenaar->Rol_idRol > 3) {
            $this->storeAcl($eigenaar->idEigenaar, $ei->structuurtable->idstructuurtable, 1, 1, 1, 1, $ei->entityrowid);
        }

        switch ($ei->entityname) {
            case 'Gegevens':
                $eigenaars = [];
                $gegevens = Gegevens::findFirst(['order' => 'idGegevens DESC']);
                if (isset($gegevens->ContractHasGegevens->Contract_idContract)) {
                    $id = $gegevens->ContractHasGegevens->Contract_idContract;
                    $st = 36;
                } else if (isset($gegevens->TaakHasGegevens->Taak_idTaak)) {
                    $id = $gegevens->TaakHasGegevens->Taak_idTaak;
                    $st = 21;
                } else if (isset($gegevens->GegevensHasOnderwerp->Onderwerp_idOnderwerp)) {
                    $id = $gegevens->GegevensHasOnderwerp->Onderwerp_idOnderwerp;
                    $st = 25;
                }
                if(isset($id)) {
                    foreach (Acl::find('Entityid = ' . $id . ' AND Structuurtable_idStructuurtable = ' . $st) as $rule) {
                        if (!in_array($rule->Eigenaar_idEigenaar, $eigenaars)) {
                            $this->storeAcl($rule->Eigenaar_idEigenaar, 38, $rule->Create, $rule->Read, $rule->Update, $rule->Delete, $$gegevens->idGegevens);
                            $eigenaars[] = $rule->Eigenaar_idEigenaar;
                        }
                    }
                }
                break;
            case 'Onderwerp':
                //get rights from contract
                $eigenaars = [];
                $onderwerp = Onderwerp::findFirst(['order' => 'idOnderwerp DESC']);
                //  foreach (Contract::find('idContract = ' . $onderwerp->Contract_idContract) as $contract) {
                if(isset($onderwerp->Contract_idContract)) {
                    $acl = Acl::find('Entityid = ' . $onderwerp->Contract_idContract . ' AND Structuurtable_idStructuurtable = 36');
                    foreach ($acl as $user) {
                        if (!in_array($user->Eigenaar_idEigenaar, $eigenaars)) {
                            $this->storeAcl($user->Eigenaar_idEigenaar, 25, $user->Create, $user->Read, $user->Update, $user->Delete, $ei->entityrowid);
                            $eigenaars[] = $user->Eigenaar_idEigenaar;
                        }
                    }
                }
                //}
                break;
            case 'Taak':

                $eigenaars = [];
                $taak = Taak::findFirst(['order' => 'idTaak DESC']);
                if ($taak->Contract_idContract > 0) {
                    foreach (Acl::find('Entityid = ' . $taak->Contract_idContract . ' AND Structuurtable_idStructuurtable = 36') as $rule) {
                        if (!in_array($rule->Eigenaar_idEigenaar, $eigenaars)) {
                            $this->storeAcl($rule->Eigenaar_idEigenaar, 21, $rule->Create, $rule->Read, $rule->Update, $rule->Delete, $taak->idTaak);
                            $eigenaars[] = $rule->Eigenaar_idEigenaar;
                        }
                    }
                } else if ($taak->Onderwerp_idOnderwerp > 0) {
                    foreach (Acl::find('Entityid = ' . $taak->Onderwerp_idOnderwerp . ' AND Structuurtable_idStructuurtable = 25') as $rule) {
                        if (!in_array($rule->Eigenaar_idEigenaar, $eigenaars)) {
                            $this->storeAcl($rule->Eigenaar_idEigenaar, 21, $rule->Create, $rule->Read, $rule->Update, $rule->Delete, $taak->idTaak);
                            $eigenaars[] = $rule->Eigenaar_idEigenaar;
                        }
                    }
                } else if (isset($taak->TaakHasGegevens->Gegevens_idGegevens)) {
                    foreach (Acl::find('Entityid = ' . $taak->Onderwerp_idOnderwerp . ' AND Structuurtable_idStructuurtable = 38') as $rule) {
                        if (!in_array($rule->Eigenaar_idEigenaar, $eigenaars)) {
                            $this->storeAcl($rule->Eigenaar_idEigenaar, 21, $rule->Create, $rule->Read, $rule->Update, $rule->Delete, $taak->idTaak);
                            $eigenaars[] = $rule->Eigenaar_idEigenaar;
                        }
                    }
                }
                break;
            case 'contract':
                //get parent
                $eigenaars = [];
                foreach (ContractHasDossier::find('Contract_idContract = ' . $ei->entityrowid) as $dossier) {
                    $acl = Acl::find('Entityid = ' . $dossier->Dossier_idDossier . ' AND Structuurtable_idStructuurtable = 26');
                    foreach ($acl as $user) {
                        if (!in_array($user->Eigenaar_idEigenaar, $eigenaars)) {
                            $this->storeAcl($user->Eigenaar_idEigenaar, 36, $user->Create, $user->Read, $user->Update, $user->Delete, $contractid);
                            $eigenaars[] = $user->Eigenaar_idEigenaar;
                        }
                    }
                }
                break;
        }


    }
}
