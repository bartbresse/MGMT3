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
