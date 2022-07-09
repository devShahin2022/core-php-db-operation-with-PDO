<?php 
// what i implement here ..
/*
1. class
2. reuseable CODE
3. data binding
4. safe string
5. pdo statement
6. secure crud all performance
7. chek insert data
8. data hashing dynamically
9. specific condition
10. add search section  
*/

// route protection
// Business logics
// api integrate
// secure image upload
// gallery management

class Database{
    private $dbhostname = "localhost";
    private $dbname = "test";
    private $dbusername = "root";
    private $dbpassword = "";
    public function __construct(){
        if(!isset($this->db)){
            try{
                // connection database
                $conn = new PDO("mysql:host=".$this->dbhostname.";dbname=".$this->dbname, $this->dbusername, $this->dbpassword); 
                $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
                $this->db = $conn; 
            }catch(Exception $e){
                die ("Database connection error : ".$e->getMessage());
            }
        }
    }
    // fetch data
    public function getData($conditions = array()){
        $data[] = "";
        $sql = "SELECT ";
        if(!empty($conditions) || array_key_exists("table",$conditions)){
            if(array_key_exists("field",$conditions)){
                if(is_array($conditions['field'])){
                    $len = count($conditions['field']);
                    $i = 1;
                    foreach ($conditions['field'] as $col) {
                        if($len == $i){
                            $sql .=  $col." ";
                        }else{
                            $sql .=  $col.", ";
                        }
                        $i++;
                    }
                }else{
                    $sql .= $conditions['field'] ;
                }
               // print_r($sql);
               // add table name
               $sql .= " FROM ".$conditions['table']. " ";
              // print_r($sql);
            }
            if(array_key_exists("condition",$conditions)&&(!array_key_exists("order_by",$conditions))&&(!array_key_exists("order_by_type",$conditions))&&(!array_key_exists("limit",$conditions))){
                if(array_key_exists("id",$conditions['condition'])){
                    // it's will be direct id access
                    $id_val = $this->safe_val($conditions['condition']['id']);
                    $sql .= " WHERE id=:id ";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':id',$id_val);
                   // print_r($stmt);
                    $stmt->execute();
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $data = array("status"=>"2003","msg"=>"single data fetch success!","data"=>$stmt->fetchAll());
                }else{
                    $sql .=" WHERE ( ";
                    // firstly check array length and then check condition type 
                    $len =count($conditions['condition']);
                    $cond_type = $conditions['condition_type'];
                    // switch case statement
                    switch ($cond_type){
                        case 'AND':
                            $res = $this->generate_conditions($conditions,$len,$sql,"AND");
                          //  print_r($res);
                            break;
                        case 'OR':
                            $res = $this->generate_conditions($conditions,$len,$sql,"OR");
                          //  print_r($res);
                            break;
                        case '<=':
                            $res = $this->generate_conditions($conditions,$len,$sql,"<=");
                          //  print_r($res);
                            break;
                        case '>=':
                            $res = $this->generate_conditions($conditions,$len,$sql,">=");
                          //  print_r($res);
                            break;
                        default: $sql;
                            break;
                    }
                    $sql = $res;
                    // loop for data binding
                    $bind_data_array = $conditions['condition'];
                    $stmt = $this->bind($sql,$bind_data_array);
                    $stmt->execute();
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    if($stmt->rowCount()>0){
                        while($row = $stmt->fetchAll()){
                            $row_data[] = $row;
                            $data = array("status"=>"2004","msg"=>"Data found success by specific condition!","data"=>$row_data);
                        }
                    }else{
                        // print_r($stmt);
                        $data = array("status"=>"2005","msg"=>"No data found!","data"=>"");
                    }
                }
            }
            if(array_key_exists("order_by",$conditions)){
                $sql .= " ORDER BY ". $this->safe_val($conditions['order_by'])."  ".$this->safe_val($conditions['order_by_type'])." ";
            }
           // print_r($sql);
           if(array_key_exists("limit",$conditions)){
                if(!empty($conditions['limit']) && is_array($conditions['limit'])){
                    $sql .= " LIMIT ".$this->safe_val($conditions['limit'][0])." OFFSET ".$this->safe_val($conditions['limit'][1]);
                }else{
                    $sql .= " LIMIT ".$this->safe_val($conditions['limit']); 
                }
           }
           //print_r($sql);
           // finally execute our queries here and fetch data
           if(!array_key_exists("condition",$conditions)){
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                if($stmt->rowCount()>0){
                while($row = $stmt->fetchAll()){
                    $row_data[] = $row; 
                }
                // print_r($data);
                $data = array("status"=>"2002","msg"=>"Data fetch success","data"=>$row_data);
                }else{
                $data = array("status"=>"2001","msg"=>"No data found!","data"=>"");
                }
           }
           if(array_key_exists("condition",$conditions) && (array_key_exists("order_by",$conditions) || array_key_exists("limit",$conditions))){
            if(array_key_exists("order_by",$conditions)){
                $sql = "SELECT * FROM ".$this->safe_val($conditions['table'])." WHERE (";
                $len = count($conditions['condition']);
                $sql = $this->generate_conditions($conditions,$len,$sql,"AND");
                $sql .= " ORDER BY ".$conditions['order_by']." ".$conditions['order_by_type']." ";
               // print_r($sql);     
            }
            if(array_key_exists("limit",$conditions)){
                if(is_array($conditions["limit"])){
                    $sql .= " LIMIT ".$this->safe_val($conditions["limit"][0])." OFFSET ".$this->safe_val($conditions["limit"][1]);
                }else{
                    $sql .= " LIMIT ".$this->safe_val($conditions["limit"]);
                }
            }
            // loop for data binding
                $bind_data_array = $conditions['condition'];
                $stmt = $this->bind($sql,$bind_data_array);
                $stmt->execute();
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                if($stmt->rowCount()>0){
                    while($row = $stmt->fetchAll()){
                        $row_data[] = $row;
                        $data = array("status"=>"2006","msg"=>"Data found success by multiple condition!","data"=>$row_data);
                    }
                }else{
               // print_r($stmt);
                    $data = array("status"=>"2007","msg"=>"No data found in this condition.","data"=>"");
                }
            // print_r($data);
           }
           return json_encode($data);
        }else{
            // echo "conditions array not be empty";
            $data = array("status"=>"2000","msg"=>"conditions array not be empty");
            return json_encode($data);
        }
    }
    // insert data
    public function insert_data($conditions_insert=array()){
        if(!empty($conditions_insert)){
            if(array_key_exists("check_clone",$conditions_insert) && !empty(array_key_exists("condition",$conditions_insert))){
                if($this->clone_check($conditions_insert,$conditions_insert['check_clone']) == 0){
                    //insert process start
                    $sql = "INSERT INTO ".$this->safe_val($conditions_insert['table'])." (";
                    // $inserts_field = implode(',',$conditions_insert['condition']); 
                    // $inserts_fields_bind = implode(',:',$conditions_insert['condition']); 
                    $len = count($conditions_insert['condition']);
                    $i = 1;
                    foreach ($conditions_insert['condition'] as $key => $value) { // i am follow this procedure for validate each field name securely
                        if($i == $len){
                            $sql.=" ".$this->safe_val($key)." ) VALUES ( ";
                        }else{
                            $sql.=" ".$this->safe_val($key)." , ";
                        }
                        $i++;
                    }
                    $i = 1;
                    foreach ($conditions_insert['condition'] as $key => $value) { // i am follow this procedure for validate each field name securely
                        if($i == $len){
                            $sql.=" :".$this->safe_val($key)." ) ";
                        }else{
                            $sql.=" :".$this->safe_val($key)." , ";
                        }
                        $i++;
                    }
                    // $sql state ment is ready
                    // data binding now
                   $res = $this->insert_hash($sql,$conditions_insert);
                    if($res[0] == 1){
                        $stmt = $res[1];
                        if($stmt->execute()){
                            $data = ["status"=>"2011","msg"=>"successfully data insert!"];
                            return json_encode($data);
                        }else{
                            $data = ["status"=>"2012","msg"=>"Internal error! please try again later."];
                            return json_encode($data);
                        }
                    }else{
                        $data = ["status"=>"2008","msg"=>"field value not be empty"];
                        return json_encode($data);
                    }
                }else{
                    $data = ["status"=>"2009","msg"=>"oops! data already exists."];
                    return json_encode($data);
                }
            }else{
                $data = ["status"=>"2010","msg"=>"oops! field duplicate or clone check does't allow empty."];
                return json_encode($data);
            }
        }
    }
    // update data
    public function update_data($dataconditions_update = array()){
        if(array_key_exists("table",$dataconditions_update) && !empty($dataconditions_update['table'])){
            if(array_key_exists("table",$dataconditions_update) && !empty($dataconditions_update['condition'])){
                if(array_key_exists("id",$dataconditions_update) && !empty($dataconditions_update['id'])){
                    //make sql query;
                    $sql = "UPDATE ". $dataconditions_update['table'] ." SET";
                    $len = count($dataconditions_update['condition']);
                    $i = 1;
                    foreach ($dataconditions_update['condition'] as $key => $value) {
                        $safe_key = $this->safe_val($key);
                        if($i == $len){
                            $sql .=" ".$safe_key." = :".$safe_key;
                        }else{
                            $sql .=" ".$safe_key." = :".$safe_key." , ";
                        }
                        $i++;
                    }
                    // data binding...
                    $res = $this->insert_hash($sql,$dataconditions_update,$dataconditions_update['id']);
                    // bind for id 
                    if($res[0] === 1){
                        $id ='';
                        $stmt = $res[1];
                        $stmt->bindParam(":id",$id);
                        $id = $dataconditions_update['id'];
                        if($stmt->execute()){
                            $data = ["status"=>"2020","msg"=>"successfully update data!"];
                            return json_encode($data);
                        }else{
                            $data = ["status"=>"2019","msg"=>"Internal error! please try again later."];
                            return json_encode($data);
                        }
                    }else{
                        $data = ["status"=>"2013","msg"=>"update field or value not be empty"];
                        return json_encode($data);
                    }
                }else{
                    $data = ["status"=>"2016","msg"=>"id does't empty"];
                    return json_encode($data);
                }
            }else{
                $data = ["status"=>"2017","msg"=>"conditions does't empty"];
                return json_encode($data);
            }
        }else{
            $data = ["status"=>"2018","msg"=>"table name not be empty"];
            return json_encode($data);
        }
    }
    // delete data
    public function del_data($conditions_delete = array()){
        if(array_key_exists("id",$conditions_delete) && !empty($conditions_delete['table'])){
            $sql = "DELETE FROM ".$conditions_delete['table']." WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $id = 0;
            $stmt->bindParam(":id",$id);
            $id = $this->safe_val($conditions_delete['id']);
            if($stmt->execute()){
                $data = ["status"=>"2021","msg"=>"Data delete success"];
                return json_encode($data);
            }else{
                $data = ["status"=>"2022","msg"=>"Internal error! try again later"];
                return json_encode($data);
            }
        }else{
            $data = ["status"=>"2023","msg"=>"fill data correctly"];
            return json_encode($data);
        }
    }
    // insert data with hash
    public function insert_hash($sql,$conditions_arr = array(),$id=''){
        if($id!='' && !empty($id)){
            $sql .= " WHERE id=:id";
        }
        $stmt = $this->db->prepare($sql);
        $confirm = 1;
        foreach ($conditions_arr['condition'] as $key => $value) {
            $safeKey = $this->safe_val($key);
            $safeVal = $this->safe_val($value);
            if(!empty($safeKey) && strlen($safeVal)>0){
                $dataBind = $safeVal;
                $stmt->bindParam(":" . $safeKey,$$dataBind); // PLEASE focus here ... important part
               // data check for hash
               if(!empty($conditions_arr['hash'])){
                $hash_len = count($conditions_arr['hash']);
                $j = 0;
                for($j;$j<$hash_len;$j++){
                    if($conditions_arr['hash'][$j] === $safeKey){
                        $hashed_data = password_hash($safeVal, PASSWORD_DEFAULT);
                        $$dataBind = $hashed_data;
                        break;
                    }else{
                        $$dataBind = $safeVal;
                    }
                }
               }else{
                $$dataBind = $safeVal;
               } 
            }else{
                $confirm = 0;
                break;
            }
        }
        return [$confirm,$stmt];
    }
    // this functin check clone data
    public function clone_check($conditions_insert=array(),$field_check){
        $safeKey = $this->safe_val($field_check);
        $dataBind = $safeKey;
        $sql = "";
        $stmt = "";
        if($field_check === 'username'){
            $sql = "SELECT username FROM ".$this->safe_val($conditions_insert['table']) ;
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $hash_arr = array();
            while ($d = $stmt->fetchAll()) {
                $hash_arr = $d; 
            }
          //  echo "<pre>";
           // print_r($hash_arr[0]['username']);
            $count_row = count($hash_arr);
            $result = 0;
            for($i=0;$i<$count_row;$i++){
                $c_hash_data = $hash_arr[$i]['username'];
                if(password_verify($conditions_insert['condition'][$safeKey], $c_hash_data)){
                    $result = 1;
                    break;
                }else{
                    $result = 0;
                    continue;
                }
            }
            return $result;
        }else{
            $sql = "SELECT * FROM ".$this->safe_val($conditions_insert['table'])." WHERE ".$safeKey."=:".$safeKey;
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":" . $safeKey,$$dataBind); // PLEASE focus here ... important part
            $$dataBind = $conditions_insert['condition'][$safeKey];

            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            if($stmt->rowCount() > 0) {
                return 1;
            }else{
                return 0;
            }
        }
    }
    // this function work for various of conditions
    public function generate_conditions($conditions=array(),$len,$sql,$cond_type){
        $i = 1;
        foreach($conditions['condition'] as $key=>$val){
            $safe_key = $this->safe_val($key);
            
            if( ($cond_type == '<=') || ($cond_type == '>=') ){
                // echo "name <br>";
                if($i == $len){
                    $sql .= $safe_key."<=:".$safe_key." ";
                }else{
                    $sql .= $safe_key.">=:".$safe_key." AND "; // if you want to change this section by some code... (AND / OR)
                }
            }else{
                if($i == $len){
                    $sql .= $safe_key."=:".$safe_key.") ";
                }else{
                    $sql .= $safe_key."=:".$safe_key." ". $cond_type ." ";
                } 
            }
            $i++;
        }
        return  $sql;
    }
    // data binding function
    public function bind($sql,$data_array=array()){
        $stmt = $this->db->prepare($sql);
        foreach ($data_array as $key => $val) {
            $safeKey = $this->safe_val($key);
            $safeVal = $this->safe_val($val);
            $dataBind = $safeKey;
            $stmt->bindParam(":" . $safeKey,$$dataBind); // PLEASE focus here ... important part
            $$dataBind = $safeVal;
        }
        return $stmt;
    }
    // get safe string section
    public function safe_val($text){
        $data = trim($text);
        return htmlspecialchars($data);
    }
}
// Fetch data call
    /*
    all condition
    1. field (array or single data)
    2. table
    3. condition and, or, id, lees than, greter than
    4. retun_field
    5. return type asc|desc
    6. limit
    7. data hash system
    */
$conditions = array(
    "field"=>"*", // it's will be both single data or array 
    "table"=>"student", // it's just single data // never array
    "condition"=>array("year"=>"2017"),
    "condition_type"=>"AND", // AND,OR,<=,>=  by default not it for id selections // if condition type
    "order_by"=>"name", // just one column
    "order_by_type"=>"ASC", // desc or asc
    "limit"=>"10", // this is single or array 
    // print_r"hash"=>["password","username"] // its work just insert data 
);
// $obj = new Database();
// print_r($obj->getData($conditions));

// insert function call
$conditions_insert = array(// key must be database col name
    "table"=>"student", // it's just single data // never array
    "condition"=>array("username"=>"Shahin2001","name"=>"Md Shahin Alam","class"=>"Hns","year"=>"2022"), // Only array support
    "check_clone"=>"username", //Just one column support // Don't support an array ....[=====IF **col check = username ** then it will be hash=====]
    "hash"=>["username"] // hasing key name always keep it an array as your wish
    // Note : please "check_clone" always keep username(which db column name) other wise it's not working properly
);
    // $obj = new Database();
    // print_r($obj->insert_data($conditions_insert));

// update function call
$conditions_update = array(
    "table"=>"student",
    "condition"=>array("username"=>"shahin2001","name"=>"Md Kawser Ahmed","class"=>"Hns","year"=>"2022"), 
    "id"=>"66", // always a single data // not an array 
    "hash"=>["username","year"]
);
    // $obj = new Database();
    // print_r($obj->update_data($conditions_update));

// delete function call
$conditions_delete = array(
    "table"=>"student",
    "id"=>"58", // always a single data // not an array 
);
//   $obj = new Database();
//   print_r($obj->del_data($conditions_delete));

?>
 