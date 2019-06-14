# MGMT3

$di->setShared('mgmt', function () {
    $request_body = json_decode(file_get_contents('php://input'), true);
    $mgmt = new MGMT();
    if (isset($request_body['data']['token'])) {
        $token = $request_body['data']['token'];
    } else {
        $token = $_GET['_url'];
    }
    $mgmt->setSecurityToken($token, ['/cron', '/file/ckeditor']);
    return $mgmt;
});

if (response.data.authentication === true) {
          var token = response.data.token
          if (token.length > 0) {
            document.cookie = 'token=' + response.data.token
            self.$router.push({ path: '/account/shop' })
          }
        }
        
        public function getAction($entityname)
    {
        $request_body = json_decode(file_get_contents('php://input'), true);
        if (isset($request_body['data']['q']) && strlen($request_body['data']['q']) > 2) {
            $q = $request_body['data']['q'];
        } else {
            $q = false;
        }

        if(isset($request_body['data']['isAddTo']) && $request_body['data']['isAddTo'] > 0)
        {
            $idvalues = [];
            $classname = ucfirst($request_body['data']['baseentity']).'Has'.ucfirst($request_body['data']['entityToAdd']);
            if(!class_exists ($classname))
            {
                $classname = ucfirst($request_body['data']['entityToAdd']).'Has'.ucfirst($request_body['data']['baseentity']);
            }

            $rows = $classname::find(ucfirst($request_body['data']['entityToAdd']).'_id'.ucfirst($request_body['data']['entityToAdd'].' = '.$request_body['data']['addToValue']));
            foreach($rows as $row)
            {
                $field = ucfirst($entityname).'_id'.ucfirst($entityname);
                $idvalues[] = $row->$field;
            }
        }else{
            $idvalues = false;
        }

        $tableData = $this->mgmt->getByEntityName($entityname, $q, $idvalues);

        echo json_encode($tableData->render(), JSON_UNESCAPED_SLASHES);
    }

    public function postAction($entity, $id = false)
    {
        $request_body = json_decode(file_get_contents('php://input'), true);
        //set owner every save

        $mgmt = $this->mgmt->getEigenaar();
        $request_body['data']['data']['Eigenaar_idEigenaar'] = $mgmt->idEigenaar;

        /*
        if($entity == 'eigenaar')
        {
            $ah = new AccountHandler();
            $request_body['data']['data'] = $ah->prepareNewAccount($request_body['data']['data']);
        } */

        $response = $this->mgmt->storeEntity($entity,$id,$request_body['data']['data']);

        echo json_encode($response, JSON_UNESCAPED_SLASHES);
    }

    public function formAction($entityname, $id = false)
    {
        $data = $this->mgmt->getFormByEntityName($entityname, $id);
        echo json_encode($data->render(), JSON_UNESCAPED_SLASHES);
    }
