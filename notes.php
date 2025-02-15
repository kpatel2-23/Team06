<?php

session_start();

include("db_config.php");

$sql = "SELECT * FROM user_notes WHERE user_id = 1";
$result = $conn->query($sql);

$_SESSION['notes'] = mysqli_fetch_array($result, MYSQLI_ASSOC)['notes'];

$pass = json_encode($_SESSION['notes']);

echo "<script>var todo_list = $pass</script>";

?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="notestyles.css">
</head>
<body onload="retrieveNotes()">
<button id="toggleButton" onclick="toggleNotepad()">
    <img id="todoimg" src="todobutton.png" alt="Todo List"/>
</button>

<div id="todo">
    <div id="todoheader">
        <button id="todobtn" onclick="addNewTodo()">Create Task</button>
    </div>
    <div id="todobody"></div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
let data = [];

function retrieveNotes()
{
    data = JSON.parse(todo_list);

    data.forEach(task => {
        addNewTodo(task.checked, task.value)
    });
}

dragElement(document.getElementById("todo"));

function dragElement(drhead) {
  var p1 = 0, p2 = 0, p3 = 0, p4 = 0;
  if (document.getElementById(drhead.id + "header")) {
    document.getElementById(drhead.id + "header").onmousedown = dragMouseDown;
  } else {
    drhead.onmousedown = dragMouseDown;
  }

  function dragMouseDown(e) {
    p3 = e.clientX;
    p4 = e.clientY;
    document.onmouseup = closeDragElement;
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    p1 = p3 - e.clientX;
    p2 = p4 - e.clientY;
    p3 = e.clientX;
    p4 = e.clientY;
    drhead.style.top = (drhead.offsetTop - p2) + "px";
    drhead.style.left = (drhead.offsetLeft - p1) + "px";
  }

  function closeDragElement() {
    document.onmouseup = null;
    document.onmousemove = null;
  }
}

function toggleNotepad()
{
    var todo = document.getElementById("todo");
    if (todo.style.display == "none")
    {
        todo.style.display = "block";
    } else {
        todo.style.display = "none";
    }
}

function addNewTodo(checked = "false", value = "New Task") {
    const todoList = document.getElementById("todobody");
    const taskDiv = document.createElement("div");
    taskDiv.setAttribute("id","taskdiv");
    data.push({checked: "false", value: ""});
    const cb = document.createElement("input");
    cb.setAttribute("type","checkbox");
    cb.setAttribute("id", "newcb");
    if(checked=="true") cb.checked = true;
    taskDiv.appendChild(cb);
    const lbl = document.createElement("label");
    lbl.setAttribute("contenteditable", "true");
    lbl.setAttribute("spellcheck", "false");
    lbl.setAttribute("id", "newlbl");
    lbl.innerHTML = value;
    taskDiv.appendChild(lbl);
    const del = document.createElement("button");
    del.setAttribute("id", "delbtn");
    del.setAttribute("onclick", "deleteTask(this)");
    del.innerHTML='<img id="deletebtn" src="delete.png" alt="Delete">'
    taskDiv.appendChild(del);
    taskDiv.appendChild(document.createElement("br"));
    todoList.appendChild(taskDiv);

}

function deleteTask(caller) {
    caller.parentElement.remove();
}

</script>

</body>
</html>
