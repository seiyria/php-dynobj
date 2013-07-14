/*
These variables needs to be defined before using DBObject:

    $location - the database location
    $database - the database name
    $username - the username to log in to the database
    $password - the password for $username

One approach is to include this information in a separate, included PHP file.
*/

$db = new PDO("mysql:host=$location;dbname=$database", $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

class DBObject
{
    private $id = 0;
    private $table;
    private $fields = array();

    function __construct($table, $fields) {
        $this->table = $table;
        foreach($fields as $key)
            $this->fields[$key] = null;
    }

    function __get($key) {
        return $this->fields[$key];
    }

    function __set($key, $value) {
        if (array_key_exists($key, $this->fields)) {
            $this->fields[$key] = $value;
            return true;
        }
        return false;
    }

    function load($id) {
        global $db;
        $res = $db->prepare(
            'SELECT * FROM '.$this->table.' WHERE '.$this->table.'_id=?'            
        );
        $res->execute(array($id));
        $row = $res->fetch(PDO::FETCH_ASSOC);
        $this->id = $id;
        foreach(array_keys($row) as $key) {
            $this->$key = $row[$key];
        }
    }

    function insert() {
        global $db;

        $fields = $this->table."_id, ";
        $fields .= join(", ", array_keys($this->fields));

        $inspoints = array("0");
        foreach(array_keys($this->fields) as $field)
            $inspoints[] = "?";

        $inspt = join(", ", $inspoints);

        $sql = "INSERT INTO ".$this->table 
            . " ($fields) VALUES ($inspt)";

        $values = array();
        foreach(array_keys($this->fields) as $field)
            $values[] = $this->fields[$field];

        $sth = $db->prepare($sql);
        $sth->execute($values);

        $res = $db->query("SELECT last_insert_id() as id");
        $row = $res->fetch(PDO::FETCH_ASSOC);
        $this->id = $row["id"];
        return $this->id;
    }

    function update() {
        global $db;

        $sets = array();
        $values = array();

        foreach(array_keys($this->fields) as $field) {
            $sets[] = $field.'=?';
            $values[] = $this->fields[$field];
        }
        $set = join(", ", $sets);
        $values[] = $this->id;

        $sql = 'UPDATE '.$this->table.' SET '.$set
               . ' WHERE '.$this->table.'_id=?';

        $sth = $db->prepare($sql);
        $sth->execute($values);
    }

    function delete() {
        global $db;
        $sth = $db->prepare(
            'DELETE FROM '.$this->table.' WHERE '.
            $this->table.'_id=?'
        );
        $sth->execute(array($this->id));
    }
}
