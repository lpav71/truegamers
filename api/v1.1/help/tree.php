<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Справка по Truegamers API</title>
		<script type="text/javascript" src="https://yastatic.net/jquery/3.3.1/jquery.min.js"></script>
		<style type="text/css">
		    a{
		        text-decoration: none;
		        color: blue;
		    }
		    a:hover{
		        text-decoration: underline;
		        color: red;
		    }
		    a.category{
		        background: transparent url('folder.png') left top no-repeat;
		        background-size: auto 16px;
		        padding-left: 20px;
		    }
		    a.article{
		        background: transparent url('document.png') left top no-repeat;
		        background-size: auto 16px;
		        padding-left: 20px;
		    }
		</style>
	</head>
	<body>
<?php
    ini_set("display_errors", 1);
    $db = new SQLite3("data.db");
    function print_tree($parent, $level){
        global $db;
        $query = "SELECT * FROM categories WHERE parent_id = $parent ORDER BY position";
        $result = $db->query($query);
        $padding = $level * 15;
        $padding = $padding."px;";
        echo "<div style=\"padding-left: $padding\">";
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            echo "<div>";
            echo '<a href="category.php?id='.$row["ID"].'" target="ARTICLE" class="category">'.$row["name"].'</a>';
            print_tree($row["ID"], $level + 1);
            echo "</div>";
        }
        $query = "SELECT * FROM articles WHERE category_id = $parent ORDER BY position";
        $result = $db->query($query);
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $row["path"] = str_replace('\\', '/', $row["path"]);
            echo "<div>";
            echo '<a href="articles/'.$row["path"].'" target="ARTICLE" class="article">'.$row["name"].'</a>';
            echo "</div>";
        }
        echo "</div>";
    }
    print_tree(0, 0);
    unset($db);
?>
	</body>
</html>