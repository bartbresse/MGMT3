<?php

use Phalcon\Mvc\User\Component;

class MGMT extends Component
{
    private $eventhandler;
    private $securityToken;
    private $security;

    public function __construct()
    {
        $this->eventhandler = new EventHandler();
        $this->audithandler = new AuditHandler();
        //TODO: event observers need to be registered somewhere else

        $this->eventhandler->registerObserver('Entity created', 'EntityCreationHandler');
        $this->eventhandler->registerObserver('Cron', 'CrontaakHandler');

        $this->languagehandler = new LanguageHandler('English');
    }

    public function cron()
    {
        $this->eventhandler->registerEvent('Cron', new EntityInfo('Taak'));
        $this->eventhandler->handleEvents();
    }

    public function getTranslation($word)
    {
        return $this->languagehandler->translate($word);
    }

    /**
     * @param $token :security JWT token
     * @param bool $urlexceptions :all urls that are excluded from a authentication token check (array)
     * @param bool $tokensdisabled :if development is true, tokens are disabled
     */
    public function setSecurityToken($token,$urlexceptions = false,$tokensdisabled = true)
    {
        $this->securityToken = $token;
        $this->security = new SecurityHandler($this->securityToken,$urlexceptions,$tokensdisabled);
        $this->acl = new AclHandler($this->security->getEigenaar());
        $this->eigenaar = $this->security->getEigenaar();
    }

    public function getEigenaar($array = false)
    {
        $eigenaar = $this->security->getEigenaar();
        if ($array) {
            return $eigenaar->toArray();
        }
        return $eigenaar;
    }

    private function getData($q,$query,$ei,$order = false)
    {
        $args = [];

        if($order)
        {
            $args['order'] = $order;
        }
        if ($q) {

            if (isset($query) && strlen($query) > 3) {
                $query .= ' AND ';
            }

            $query .= ' ( ';
            $structuur = Structuur::find('tabel = "' . strtolower($ei->tablename) . '" AND istablefield > 0');
            foreach ($structuur as $index => $field) {
                if ($index > 0) {
                    $query .= ' OR ' . $field->field . ' LIKE "%' . $q . '%"';
                } else {
                    $query .= '' . $field->field . ' LIKE "%' . $q . '%"';
                }
            }
            $query .= ' ) ';

            $args['conditions'] = $query;

            return $ei->entityname::find($args);
        } else if ($this->eigenaar->Rol_idRol < 4 || strlen($query) > 5) {
            if (strlen($query) < 5) {
                return $ei->entityname::find($args);
            } else {
                $args['condtions'] = $query;

                return $ei->entityname::find();
            }
        } else {
            return [];
        }
    }

    /**
     * getAction
     *
     * @param $entityname
     * @param bool $q
     * @param bool $idvalues
     * @return TableJsonHandler
     */
    public function getByEntityName($entityname, $q = false, $idvalues = false, $order = false)
    {
        $ei = new EntityInfo($entityname);
        $ei->setStructuurtable(Structuurtable::findFirst('sqltable = "'.$entityname.'"'));
        //SEARCH QUERY $Q

        $ei->data = $this->getData($q,$this->acl->getAllowedRowsQuery($ei),$ei,$order);

        $fjh = new FormJsonHandler($ei);
        $fjh->setLanguageHandler($this->languagehandler);
        $json = new TableJsonHandler($ei);

        $json->setLanguageHandler($this->languagehandler);
        $json->setRightsPerRow($this->acl->rightsPerRow);
        $json->setSelectedRows($idvalues);
        $json->setFormJson($fjh->render());

        return $json;
    }

    /**
     * viewAction
     *
     * @param $entityname
     * @param $entityRowId
     * @return TableJsonHandler
     */
    public function getRowByEntityNameAndId($entityname, $entityRowId)
    {
        $ei = new EntityInfo($entityname);

        $structuurtable = Structuurtable::findFirst('sqltable = "'.$entityname.'"');
        $ei->setStructuurtable($structuurtable);
        $ei->entityrowid = $entityRowId;

        $sech = new SecurityHandler($this->securityToken);
        $sech->getEigenaarID();

        $this->viewrights = $this->acl->getRights($ei);
        $ei->data = $ei->entityname::findFirst('id' . $ei->entityname . ' = "' . $entityRowId . '"');
        $ei->detailview = true;

        $fjh = new FormJsonHandler($ei);
        $json = new TableJsonHandler($ei);
        $fjh->setLanguageHandler($this->languagehandler);
        $json->setRightsPerRow($this->acl->rightsPerRow);
        $json->setLanguageHandler($this->languagehandler);
        $json->setFormJson($fjh->render());
        return $json;
    }

    public function getRights()
    {
        return $this->viewrights;
    }


    /**
     * @param $entityname
     * @param $entityRowId
     * @return FormJsonHandler
     */
    public function getFormByEntityName($entityname, $entityRowId)
    {
        $ei = new EntityInfo($entityname, $entityRowId);
        $fjh = new FormJsonHandler($ei);
        $fjh->setLanguageHandler($this->languagehandler);
        return $fjh;
    }

    /**
     * store form
     *
     * @param $entityname
     * @param $entityRowId
     * @return StorageHandler
     */
    public function storeEntity($entityname, $entityRowId, $data)
    {
        $ei = new EntityInfo($entityname, $entityRowId);
        $ei->setStructuurtable(Structuurtable::findFirst('sqltable = "'.$entityname.'"'));

        if ($entityRowId > 0) {
            $oldrow = $ei->entityname::findFirst('id' . $ei->entityname . ' = "' . $ei->entityrowid . '"');
            $oldrow = $oldrow->toArray();
        } else {
            $oldrow = [];
        }

        $fjh = new FormJsonHandler($ei);
        $fjh->setLanguageHandler($this->languagehandler);

        $sh = new StorageHandler($ei);
        $sh->setFormRender($fjh->render());
        $data = $sh->store($data,$this->security->getEigenaarID());

        if($data['result'] == 'succes') {
            $ei->entityrowid = $data['id'];
            $storeacl = new StoreaclHandler();
            $storeacl->newRule($ei, $data, $this->eigenaar);

            if ($entityRowId) {
                $this->audithandler->add('update', $this->security->getEigenaarID(), $ei, $oldrow);
                $this->eventhandler->registerEvent('Entity Updated', $ei);
            } else {
                $ei->entityrowid = $data['id'];
                $this->audithandler->add('add', $this->security->getEigenaarID(), $ei, $oldrow);
                $this->eventhandler->registerEvent('Entity created', $ei);
            }
            $this->eventhandler->handleEvents();
        }
        return $data;
    }

    /**
     * @param $entityname
     * @param $entityRowId
     * @return array
     *
     * TODO: figure out where to put this.
     */
    public function getByEntityAndIdName($entityname, $entityRowId)
    {
        $mysql = new MySQLQueries();

        $ucfirst_entity = ucfirst($entityname);
        $ue = $ucfirst_entity::findFirst('id' . $ucfirst_entity . ' = "' . $entityRowId . '"');
        $jsondata = [];

        foreach ($mysql->getTables($entityname) as $table2) {

            $ei = new EntityInfo($table2['TABLE_NAME']);
            $ei->setStructuurtable(Structuurtable::findFirst('sqltable = "'.$table2['TABLE_NAME'].'"'));

            if (!$ei->isManyToMany()) {
                $value = 'id' . $ucfirst_entity;

                $query = $this->acl->getAllowedRowsQuery($ei);

                if($query) {
                    $ei->data = $ei->entityname::find($query.' AND '.(ucfirst($table2['REFERENCED_TABLE_NAME'])) . '_' . 'id' . (ucfirst($table2['REFERENCED_TABLE_NAME'])) . ' = "' . $ue->$value . '"');
                } else {
                    $ei->data = $ei->entityname::find(ucfirst($table2['REFERENCED_TABLE_NAME']) . '_' . 'id' . (ucfirst($table2['REFERENCED_TABLE_NAME'])) . ' = "' . $ue->$value . '"');
                }

                $json = new TableJsonHandler($ei);
                $json->setLanguageHandler($this->languagehandler);
                //TODO: fill rights per row
                $json->setRightsPerRow($this->acl->rightsPerRow);
                $fjh = new FormJsonHandler($ei);
                $fjh->setLanguageHandler($this->languagehandler);
                $json->setFormJson($fjh->render());

                $render = $json->render();
                if (count($render['data']) > 0) {
                    $jsondata[$ei->entityname] = $render;
                    $jsondata[$ei->entityname]['entityname'] = $ei->entityname;
                }
            } else { //is many to many

                //TODO: this is not generic, get this to work for all relations
                $value = 'id' . $ucfirst_entity;

                //turn databasetablename into classname
                $en = '';

                foreach (explode('_', strtolower($ei->entityname)) as $part) {
                    $en .= ucfirst($part);
                    if ($part != strtolower($entityname) && $part != 'has') {
                        $referencedtablename = ucfirst($part);
                    } else if ($part != 'has') {
                        $hometablename = ucfirst($part);
                    }
                }

                $ei->entityname = $en;

                $query = $this->acl->getAllowedRowsQuery($ei);
                $entitydata = $ei->entityname::find($hometablename . '_' . 'id' . $hometablename . ' = "' . $ue->$value . '"');
                $data = [];

                foreach ($entitydata as $row) {
                    $data[] = $row->$referencedtablename;
                }

                $ei->data = $data;

                $ei->entityname = $referencedtablename;
                $ei->tablename = strtolower($referencedtablename);

                $json = new TableJsonHandler($ei);
                $json->setLanguageHandler($this->languagehandler);
                //TODO: fill rights per row
                $json->setRightsPerRow($this->acl->rightsPerRow);
                $fjh = new FormJsonHandler($ei);
                $fjh->setLanguageHandler($this->languagehandler);
                $json->setFormJson($fjh->render());

                $jsondata[$ei->entityname] = $json->render();
                $jsondata[$ei->entityname]['entityid'] = $entityRowId;
                $jsondata[$ei->entityname]['entityname'] = $ei->entityname;
            }
        }

        return $jsondata;
    }

    /**
     * Audit system: restore a row to a previous point
     */
    public function restoreRow($entity,$id,$auditid)
    {
        $audit = Audit::findFirst('idAudit = '.$auditid);
        $fields = [];
        foreach (json_decode($audit->Oldrow) as $index => $oldrow)
        {
            $fields[$index] = ['value' => $oldrow];
        }

        $this->mgmt->storeEntity($entity,$id,$fields);
        return $this->listAudit($entity,$id);
    }

    public function uploadFile($token)
    {
        $security = $this->security->getPayload();
        $fileHandler = new FileHandler($security);
        return $fileHandler->uploadFile();
    }

    /**
     * Audit system:
     */
    public function listAudit($entity,$id)
    {
        $audithandler = new AuditHandler();
        return $audithandler->listAudit($entity,$id);
    }

    public function __destruct()
    {
      //  $this->eventhandler->execute();
    }
}
