<?php

include("db_config.php");

$sql = "SELECT * FROM user_notes WHERE user_id = " . $_SESSION['user_id'];
$result = $conn->query($sql);

if($result->num_rows==0)
{
    $temp_user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO user_notes(user_id, notes) VALUES ($temp_user_id, 'null')";
    $conn->query($sql);

    $sql = "SELECT * FROM user_notes WHERE user_id = " . $_SESSION['user_id'];
    $result = $conn->query($sql);
}

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
    <div id="note-container">
        <button id="toggleButton" onclick="toggleNotepad()">
            <img id="todoimg" src="todobutton.png" alt="Todo List"/>
        </button>

    <div id="todo">
        <div id="todoheader" alt="Drag Me!">
            <button id="todobtn" onclick="addNewTodo()">Create Task</button>
        </div>
        <div id="todobody"></div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
let data = [];
let lbls = [];

window.addEventListener("beforeunload", function(event) {
    saveNotes();
    event.returnValue = "";
});

function saveNotes() {
    const data = data.map(task => task.value);

    $.ajax({
        url: "setnotes.php",
        type: "POST",
        data: JSON.stringify(data),
        contentType: "application/json",
        success: function(response) {

        }, 
        error: function(xhr, status, error) {

        }
    });
}

function retrieveNotes()
{
    todo.style.display = "none"
    if(todo_list=="null") {
        addNewTodo("false", "Edit Me!");
    } else {
        const tasks = JSON.parse(todo_list);
        tasks.forEach(taskValue => {
            addNewTodo("false", taskValue); // Create unchecked tasks with the values
        });
    }

}

const dragTodo = document.getElementById("todo");
const dragTodoHeader = document.getElementById("todoheader");

dragTodoHeader.addEventListener("mousedown", function(event) {
    event.preventDefault(); // Prevent text selection while dragging
    
    const rect = dragTodo.getBoundingClientRect();
    const shiftX = event.clientX - rect.left;
    const shiftY = event.clientY - rect.top;

    // Set initial position to absolute for dragging
    dragTodo.style.position = 'fixed';
    
    function moveAt(pageX, pageY) {
        let newX = pageX - shiftX;
        let newY = pageY - shiftY;
        
        // Get todo dimensions
        const todoWidth = dragTodo.offsetWidth;
        const todoHeight = dragTodo.offsetHeight;
        
        // Prevent dragging outside window bounds
        newX = Math.max(0, Math.min(newX, window.innerWidth - todoWidth));
        newY = Math.max(0, Math.min(newY, window.innerHeight - todoHeight));
        
        dragTodo.style.left = newX + 'px';
        dragTodo.style.top = newY + 'px';
    }

    function onMouseMove(event) {
        moveAt(event.clientX, event.clientY);
    }

    // Move on mousemove
    document.addEventListener('mousemove', onMouseMove);

    // Stop moving on mouseup
    document.addEventListener('mouseup', function() {
        document.removeEventListener('mousemove', onMouseMove);
        dragTodo.style.position = 'fixed'; // Keep it fixed after dragging
    }, { once: true });
});

// Prevent default drag behavior
dragTodoHeader.ondragstart = function() {
    return false;
};

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
    data.push({checked: checked, value: value});
    const cb = document.createElement("input");
    cb.setAttribute("type","checkbox");
    cb.setAttribute("id", "newcb");
    if(checked=="true") cb.checked = true;
    cb.addEventListener("change", function() {
        updateCheck(this);
    });
    taskDiv.appendChild(cb);
    const lbl = document.createElement("label");
    lbl.setAttribute("contenteditable", "true");
    lbl.setAttribute("spellcheck", "false");
    lbl.setAttribute("id", "newlbl");
    lbl.innerHTML = value;
    lbl.addEventListener("input", function() {
        updateTask(this);
    });
    taskDiv.appendChild(lbl);
    const del = document.createElement("button");
    del.setAttribute("id", "delbtn");
    del.setAttribute("onclick", "deleteTask(this)");
    del.innerHTML='<img id="deletebtn" src="delete.png" alt="Delete">'
    taskDiv.appendChild(del);
    taskDiv.appendChild(document.createElement("br"));
    lbls.push(taskDiv);
    todoList.appendChild(taskDiv);
}

function updateCheck(caller) {
    const lblIndex = lbls.indexOf(caller.parentElement);
    data[lblIndex].checked = caller.checked.toString();
}

function updateTask(caller) {
    const lblIndex = lbls.indexOf(caller.parentElement);
    data[lblIndex].value = caller.innerHTML;
}

function deleteTask(caller) {
    const lblIndex = lbls.indexOf(caller.parentElement);
    data.splice(lblIndex, 1);
    lbls.splice(lblIndex, 1);
    caller.parentElement.remove();
}

</script>

</body>
</html>