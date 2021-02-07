<?php
class SprDatabase {
    const ASC  = 1;
    const DESC = 0;
    const JOIN_LEFT  = "LEFT";
    const JOIN_RIGHT = "RIGHT";
    const JOIN_INNER = "INNER";

    public function __construct($spr) {
        $this->spr = $spr;
        $config_file = join([$spr["approot"], "/config.json"]);
        $this->config = json_decode(file_get_contents($config_file))->db;
    }

    public function connect() {
        $this->pdo = new PDO($this->config->connstr, $this->config->user, $this->config->pass);
    }

    public function table(string $table_name) {
        $this->$table_name = $table_name;
        return new SprDatabaseLinq($this, $table_name);
    }
}

class SprDatabaseLinq {
    public function __construct($db, $table_name) {
        $this->db = $db;
        $this->table_name = $table_name;
    }

    public function getSqlState($alias_name) {
        foreach (self::SQLSTATE as $code => $alias) {
            if ($alias_name === $alias[0]) { return $code; }
        }
        return null;
    }

    public function where($where = "") {
        if (is_array($where)) {
            $wheres = [];
            $where_binddata = [];
            foreach ($where as $filed => $value) {
                if (is_array($value)) {
                    // { "field" => [op, data] }    field op ：field
                    $op = $value[0]; $data = $value[1];
                } else {
                    // { "field" => data }          filed = :field
                    $op = "=";       $data = $value;
                }
                $wheres[] = "$filed $op :where_$filed";
                $where_binddata["where_$filed"] = $data;
            }
            $where = join(" AND ", $wheres);
        } else {
            $where_binddata = null;
        }
        $this->where_stmt = "WHERE $where";
        $this->where_bind = $where_binddata;
        return $this;
    }

    public function orderby($order) {
        $order_stmt = [];
        foreach ($order as $filed => $seq) {
            $seq_stmt = ($seq === Database::DESC) ? "DESC" : "ASC";
            $order_stmt[] = "$filed $seq_stmt";
        }
        $order_stmt = join(", ", $order_stmt);
        $this->order_stmt = "ORDER BY $order_stmt";
        return $this;
    }

    public function groupby($group) {
        if (is_array($group)) { $group = join(", ", $group); }
        $this->group_stmt = "GROUP BY $group";
        return $this;
    }

    public function limit(int $page_size, int $page_number = 1) {
        $offset = $page_size * ($page_number - 1);
        $this->limit_stmt = "LIMIT $page_size OFFSET $offset";
        return $this;
    }

    public function join($join_table, $join_cond, $join_type="LEFT") {
        $this->join_stmt = "$join_type JOIN $join_table ON $join_cond";
        return $this;
    }

    public function select($select="*", $diff=false) {
        $diff_stmt = $diff ? "DISTINCT" : "";
        if (is_array($select)) {
            $select = join(", ", $select);
        }
        $this->left_stmt = "SELECT $diff_stmt $select FROM";
        return $this;
    }

    public function update($data) {
        $this->left_stmt = "UPDATE FROM";
        $stmt = []; $bind = [];
        foreach ($data as $filed => $val) {
            $stmt[] = "$filed = :data_$filed";
            $bind["data_$filed"] = $val;
        }
        $stmt = join(", ", $stmt);
        $this->data_stmt = "SET $stmt";
        $this->data_bind = $bind;
        return $this;
    }

    public function insert($data) {
        $this->left_stmt = "INSERT INTO";
        $stmt_l = []; $stmt_r = []; $bind = [];
        foreach ($data as $filed => $val) {
            $stmt_l[] = $filed;
            $stmt_r[] = ":data_$filed";
            $bind["data_$filed"] = $val;
        }
        $stmt_l = join(", ", $stmt_l);
        $stmt_r = join(", ", $stmt_r);
        $this->data_stmt = "($stmt_l) VALUES ($stmt_r)";
        $this->data_bind = $bind;
        return $this;
    }

    public function delete() {
        $this->left_stmt = "DELETE FROM";
        return $this;
    }

    public function getSQLStatement($ignore_where_warning=false) {
        $operation_type = substr($this->left_stmt, 0, 6);
        $data =  isset($this->data_stmt)  ? $this->data_stmt  : "";
        $join =  isset($this->join_stmt)  ? $this->join_stmt  : "";
        $group = isset($this->group_stmt) ? $this->group_stmt : "";
        $where = isset($this->where_stmt) ? $this->where_stmt : "";
        $order = isset($this->order_stmt) ? $this->order_stmt : "";
        $limit = isset($this->limit_stmt) ? $this->limit_stmt : "";
        if (($where === "") && in_array($operation_type, ["UPDATE", "DELETE"]) && !$ignore_where_warning) {
            throw new Exception("WHERE statement must be set on $operation_type operation, or use get_sql(ignore_where_warning: true)");
        }
        $sql = [];
        $sql["SELECT"] = "{$this->left_stmt} {$this->table_name} $join $where $group $order $limit";
        $sql["UPDATE"] = "{$this->left_stmt} {$this->table_name} $data $where";
        $sql["INSERT"] = "{$this->left_stmt} {$this->table_name} $data";
        $sql["DELETE"] = "{$this->left_stmt} {$this->table_name} $where";
        return $sql[$operation_type];
    }

    public function query($ignore_where_warning=false) {
        $sql = $this->getSQLStatement($ignore_where_warning);
        $this->stmt = $this->db->pdo->prepare($sql);
        if (isset($this->where_bind)) {
            foreach ($this->where_bind as $param => $value) {
                $data_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $this->stmt->bindValue($param, $value, $data_type);
            }
        }
        if (isset($this->data_bind)) {
            foreach ($this->data_bind as $param => $value) {
                $data_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $this->stmt->bindValue($param, $value, $data_type);
            }
        }
        $this->stmt->execute();
        return $this;
    }

    public function toArray() {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toClass() {
        return $this->stmt->fetchAll(PDO::FETCH_CLASS);
    }
}
