<?php
  $filter = $_REQUEST["filter"];
  if (empty($filter)){
    die(format_result("ERROR_EMPTY_FILTER"));
  }
  $filter = json_decode($filter, true);
  $branch_id = $filter["branch_id"];
  if (!is_array($branch_id)){
    die(format_result("ERROR_INVALID_BRANCH_ID"));
  }
  if (!empty($branch_id)){
    $branch_filter = "WHERE branch_id IN(".implode(',', $branch_id).")";
  }
  else {
    $branch_filter = "WHERE branch_id IN (".$allowed_branches_subquery.")";
  }
  $sort = $_REQUEST["sort"];
  if (empty($sort)){
    $field = "product";
    $direction = "ASC";
  }
  else {
    $sort = json_decode($sort, true);
    if (is_array($sort)){
      $field = '"'.$sort["field"].'"';
      $direction = $sort["order"];
    }
    else {
      $field = "product";
      $direction = "ASC";
    }
  }
  if ($_SERVER["HTTP_X_CLIENT"] == "ClientShell"){
    $where = "$branch_filter AND shell = 1";
  }
  else {
    $where = $branch_filter;
  }
  $b_filter = str_replace('branch_id', '"ID"', $branch_filter);
  $query = "SELECT \"ID\" AS \"id\", * FROM tg_branches $b_filter";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $branches = array();
  while ($branch = pg_fetch_array($result)){
    $branches[intval($branch["ID"])] = normalize_array($branch, array("ID", "active"));
  }
  $queries[] = $branches;
  $query = "SELECT \"ID\" AS \"id\", * FROM tg_store $where ORDER BY $field $direction";
  $queries[] = $query;
  $result = pg_query($query) or die(database_query_error($query, __FILE__, __LINE__));
  $items[] = array();
  while ($item = pg_fetch_array($result)){
    $item = normalize_array($item, array("ID"));
    if (empty($item)){
      continue;
    }
    $item["branch"] = $branches[intval($item["branch_id"])];
    unset($item["branch_id"]);
    $items[] = $item;
  }
  $queries[] = $items;
  $output = array("result"=>"RESULT_SUCCESS");
  $output["payload"] = array();
  $output["payload"]["items"] = $items;
  $output["payload"]["totalCount"] = count($items);
  exit(format_array($output));
?>
