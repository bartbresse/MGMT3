private function getStructuurDatabaseVariable($field, $table)
    {
        $structuur = Structuur::findFirst('field = "' . $field['Field'] . '" AND tabel = "' . $table . '"');
        if (!$structuur) {
            $structuur = new Structuur();
            if (!isset($field['isTableField'])) {
                $field['isTableField'] = true;
            }
            $structuur->istablefield = $field['isTableField'];
            $structuur->field = $field['Field'];
            $structuur->name = $field['Field'];
            $structuur->title = ''; //inputting these is not nescessary unless a form has a different label
            $structuur->placeholder = ''; //inputting these is not nescessary unless a form has a different description
            $structuur->tabel = $table;
            if (!$structuur->save()) {
                //   print_r($structuur->getMessages());
            }
        }
        return $structuur;
    }

    private function generateRelationalField($table, $orm, $id, $row)
    {
        //TODO: this is only used for the structuur->cssclass field
        $structuur = $this->getStructuurDatabaseVariable(['Field' => $orm->foreign_structuurname, 'isTableField' => false], $table);
        $f = $structuur->toArray();
        $f['attr'] = json_decode($f['attr'], true);
        $f['name'] = $orm->foreign_classname;

        //TODO remove tight coupling language class
        if (isset($this->language)) {
            $f['title'] = $this->language->translate($orm->foreign_classname);
        } else {
            $f['title'] = $orm->foreign_classname;
        }
        $f['field'] = $orm->tablename;
        $f['tabel'] = $orm->tablename;
        $f['description'] = ' ';
        $f['visible'] = true;
        $f['message'] = '';
        $f['placeholder'] = $orm->foreign_classname;
        if ($orm->isMany()) { //manyToMany
            //TODO: figure out how to do this better
            if ($orm->foreign == 'document') {
                $f['filelist'] = $this->getSelectFileList($orm->tablename, $id);
                $f['type'] = 'files';
            } else {
                $f['enum'] = $this->getSelectList($orm->foreign_classname);
                $f['entity'] = strtolower($orm->foreign_classname);
                $f['type'] = 'multiselect';


            }
            //TODO: make multiple select option values possible with front end
            if (is_object($row)) {
                $class = $orm->classname;
                $foreignid = $orm->foreign_id;
                foreach ($row->$class as $sub) {
                    $f['value'] = [$sub->$foreignid];
                }
            }


        } else { //oneToMany
            if ($orm->tablename == 'document') {
                $f['filelist'] = $this->getSelectFileList($orm->tablename, $id);
                $f['type'] = 'files';
            } else {
                $f['enum'] = $this->getSelectList($orm->classname);
                $f['entity'] = strtolower($orm->classname);
                $f['type'] = 'multiselect';
            }
        }
        if (!isset($f['value'])) {
            $f['value'] = '';
        }
        return $f;
    }

    public function getForeignRelationsDefinedInCode($table, $id, $row)
    {
        $fields = [];
        $classname = ucfirst($table);
        $array = $this->modelsManager->getRelations($classname);
        $array1 = $this->modelsManager->getBelongsTo(new $classname());
        $array2 = $this->modelsManager->getHasOneAndHasMany(new $classname());
        foreach ([$array1, $array2, $array] as $relations) {
            foreach ($relations as $relation) {
                $orm = new OrmEntity();
                $ex = explode('Has', stripslashes($relation->getReferencedModel()));
                if (isset($ex[1])) {
                    $tablename = strtolower($ex[0]) . '_has_' . strtolower($ex[1]);
                    $orm->setTableName($tablename);
                    $orm->setLocalEntity($table);
                    $fields[$orm->tablename] = $this->generateRelationalField($table, $orm, $id, $row);
                }else{
                    $tablename = strtolower($ex[0]);
                    $orm->setTableName($tablename);
                    $orm->setLocalEntity($table);
                    $fields[$orm->tablename] = $this->generateRelationalField($table, $orm, $id, $row);
                }
            }
        }
        return $fields;
    }
