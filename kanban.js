document.addEventListener("DOMContentLoaded", function () {
    let draggedTask = null;

    // Event listener for the edit button to load the Kanban board
    document.querySelectorAll(".edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            const projectId = this.getAttribute("data-project-id");
            loadKanbanBoard(projectId);
        });
    });

    // Function to load the Kanban board
    function loadKanbanBoard(projectId) {
        fetch('fetch_kanban_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `project_id=${projectId}`
        })
        .then(response => response.json())
        .then(data => {
            displayKanbanBoard(data);
        })
        .catch(error => console.error('Error:', error));
    }

    // Function to display the Kanban board
    function displayKanbanBoard(data) {
        const modal = document.createElement("div");
        modal.className = "kanban-modal";
        modal.innerHTML = `
            <div class="kanban-modal-content">
                <button class="close-kanban" style="color: black; font-size: 24px; background: none; border: none; cursor: pointer; position: absolute; top: 10px; right: 10px;">✕</button>
                <h2>Kanban Board: ${data.project.title}</h2>
                <div class="kanban-board"></div>
            </div>
        `;
        document.body.appendChild(modal);

        const kanbanBoard = modal.querySelector(".kanban-board");

        // Create columns for each employee
        data.employees.forEach(employee => {
            const column = document.createElement("div");
            column.className = "kanban-column";
            column.setAttribute("data-employee-id", employee.id);
            column.innerHTML = `<h3>${employee.name}</h3>`;
            kanbanBoard.appendChild(column);

            // Add tasks to the employee's column
            const tasks = data.tasks.filter(task => task.employee_id === employee.id);
            tasks.forEach(task => {
                const taskCard = createTaskCard(task);
                column.appendChild(taskCard);
            });
        });

        // Close modal when clicking the close button
        modal.querySelector(".close-kanban").addEventListener("click", () => {
            modal.remove();
            // Refresh the manager dashboard to reflect changes
            location.reload();
        });

        // Enable drag-and-drop functionality
        enableDragAndDrop();
    }

    // Function to create a task card
    function createTaskCard(task) {
        const taskCard = document.createElement("div");
        taskCard.className = "task-card";
        taskCard.setAttribute("data-task-id", task.id);
        taskCard.draggable = true;
        taskCard.innerHTML = `
            <h4>${task.title}</h4>
            <p>${task.description}</p>
            <p>Deadline: ${task.deadline}</p>
            <p>Priority: ${task.priority}</p>
        `;
        return taskCard;
    }

    // Enable drag-and-drop functionality
    function enableDragAndDrop() {
        const taskCards = document.querySelectorAll(".task-card");
        const columns = document.querySelectorAll(".kanban-column");

        // Drag start event
        taskCards.forEach(taskCard => {
            taskCard.addEventListener("dragstart", (e) => {
                draggedTask = taskCard;
                setTimeout(() => {
                    taskCard.style.display = "none";
                }, 0);
            });
        });

        // Drag end event
        taskCards.forEach(taskCard => {
            taskCard.addEventListener("dragend", () => {
                draggedTask.style.display = "block";
                draggedTask = null;
            });
        });

        // Drag over event for columns
        columns.forEach(column => {
            column.addEventListener("dragover", (e) => {
                e.preventDefault();
                const afterElement = getDragAfterElement(column, e.clientY);
                if (afterElement == null) {
                    column.appendChild(draggedTask);
                } else {
                    column.insertBefore(draggedTask, afterElement);
                }
            });
        });

        // Drop event for columns
        columns.forEach(column => {
            column.addEventListener("drop", () => {
                const newEmployeeId = column.getAttribute("data-employee-id");
                const taskId = draggedTask.getAttribute("data-task-id");

                // Update task assignment in the database
                fetch('update_task_assignment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&employee_id=${newEmployeeId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Task reassigned successfully!");
                        // Remove the task card from its old column
                        draggedTask.remove();
                        // Add the task card to the new column
                        column.appendChild(draggedTask);
                    } else {
                        console.error("Failed to reassign task.");
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    }

    // Helper function to determine where to place the dragged task
    function getDragAfterElement(column, y) {
        const draggableElements = [...column.querySelectorAll(".task-card:not(.dragging)")];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
});