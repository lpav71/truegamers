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
    //ini_set("display_errors", 1);
    $db = new SQLite3("data.db");
    $id = intval($_GET["id"]);
    if (! isset($_GET["id"])){
        echo '<div style="color:red">Не указан ID категории</div>';
    }
    else{
        $query = "SELECT * FROM categories WHERE \"ID\" = $id";
        $result = $db->query($query);
        $category = $result->fetchArray(SQLITE3_ASSOC);
        if ($id == 0){
            $category = array("parent_id"=>0, "name"=>"Справка по Truegamers API");
        }
        if (! $category){
            echo '<div style="color:red">Категория не найдена</div>';
        }
        else{
            echo '<h1>'.$category["name"].'</h1><hr />';
            if ($category["parent_id"] != 0){
                echo '<a href="category.php?id='.$category["parent_id"].'">Родительская категория</a><hr />';
            }
            $query = "SELECT * FROM categories WHERE parent_id = $id ORDER BY position";
            $result = $db->query($query);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)){
                echo "<div>";
                echo '<a href="category.php?id='.$row["ID"].'" class="category">'.$row["name"].'</a>';
                echo "</div>";
            }
            $query = "SELECT * FROM articles WHERE category_id = $id ORDER BY position";
            $result = $db->query($query);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)){
                echo "<div>";
                $row["path"] = str_replace('\\', '/', $row["path"]);
                echo '<a href="articles/'.$row["path"].'" class="article">'.$row["name"].'</a>';
                echo "</div>";
            }
        }
    }
    unset($db);
?>
	</body>
</html>