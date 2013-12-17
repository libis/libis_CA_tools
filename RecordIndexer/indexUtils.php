<?php
/**
 * User: NaeemM
 * Date: 9/12/13
 */

require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");

class indexUtils {
    protected $opo_datamodel;
    protected $searchIndexer;
    protected $searchBase;
    protected $opo_engine;
    protected $opo_db;

    public function __construct(){
        $this->opo_datamodel = Datamodel::load();
        $this->searchIndexer = new SearchIndexer();
        $this->searchBase = new SearchBase();

        if (!($this->opo_engine = SearchBase::newSearchEngine(null, 57))) {
            die("Couldn't load configured search engine plugin. Check your application configuration and make sure 'search_engine_plugin' directive is set properly.");
        }
        $this->opo_db = new Db();
    }

    public function indexRecord($vs_table, $vn_table_num, $object_id){
        $o_db = $this->opo_db;
        $t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
        $va_fields_to_index = $this->searchBase->getFieldsToIndex($vn_table_num);
        if (!is_array($va_fields_to_index) || (sizeof($va_fields_to_index) == 0)) {
            return null;
        }

        if(isset($object_id))
            $str_query = "SELECT ".$t_instance->primaryKey()." FROM $vs_table"." WHERE object_id = ".$object_id." AND deleted = 0"; //currently only for ca_objects
        else
            $str_query = "SELECT ".$t_instance->primaryKey()." FROM ".$vs_table;

        $qr_all = $o_db->query($str_query);
        $vn_num_rows = $qr_all->numRows();
        echo "\n\rTotal rows to be indexed: ".$vn_num_rows." of table ".$vs_table." \n\r";

        $counter = 1;
        while($qr_all->nextRow()) {
            echo "Indexing row ".$counter. "/".$vn_num_rows." of ".$vs_table." \n\r";
            $t_instance->load($qr_all->get($t_instance->primaryKey()));
            $t_instance->doSearchIndexing(array(), true, $this->opo_engine->engineName());
            $counter++;
        }
        $this->opo_engine->optimizeIndex($vn_table_num);
    }

    function getObject($idNo){
        $o_db = $this->opo_db;
        $qr_res =$o_db->query("
					SELECT *
					FROM ca_objects
					WHERE idno = ?
				", $idNo);
        if(isset($qr_res)){
            $qr_res->nextRow();
            return $qr_res->get('object_id');
        }
        else
            return null;
    }

    function tablestoIndex(){
        return $this->searchIndexer->getIndexedTables();
    }


    function indexOneTableAllRecords($table_name){
        $va_table_names = $this->tablestoIndex();
        foreach($va_table_names as $vn_table_num => $va_table_info) {
           if($va_table_info['name'] === $table_name)
               $this->indexRecord($table_name, $vn_table_num, null);
        }
    }

    function indexOneTableOneRecord($table_name, $id_no){ //currently only for ca_objects
        if($table_name !== "ca_objects")
            return "One-record-indexing is currently available only for ca_objects table";

        $object_id = $this->getObject($id_no);
        if(isset($object_id)){
            $va_table_names = $this->tablestoIndex();
            foreach($va_table_names as $vn_table_num => $va_table_info) {
                if($va_table_info['name'] === $table_name)
                    $this->indexRecord($table_name, $vn_table_num, $object_id);
            }
        }
        else
            return "object with IDNO: ".$id_no. " does not exist";
    }

    function indexAllTablesAllRecords(){            //full indexing
        $va_table_names = $this->tablestoIndex();
        foreach($va_table_names as $vn_table_num => $va_table_info) {
            $vs_table_name = $va_table_info['name'];
            $this->indexRecord($vs_table_name, $vn_table_num, null);
        }
    }

    function removeAnIndex($table_name, $id_no){    //currently only for ca_objects
        if($table_name !== "ca_objects")
            return "Unindexing is currently available only for ca_objects table";
        $object_id = $this->getObject($id_no);
        if(isset($object_id)){
            $va_table_names = $this->tablestoIndex();
            foreach($va_table_names as $vn_table_num => $va_table_info) {
                if($va_table_info['name'] === $table_name){
                    echo "Unindexing: ".$table_name. "/".$vn_table_num."/".$object_id." \n\r";
                    $this->searchIndexer->startRowUnIndexing($vn_table_num, $object_id);
                    $this->searchIndexer->commitRowUnIndexing($vn_table_num, $object_id);
                }
            }
        }
        else
            return "object with IDNO: ".$id_no. " does not exist";
    }

} 