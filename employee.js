
// Notification system
function showNotification(message, type) {
    const notificationContainer = document.getElementById("notificationContainer");

    const notification = document.createElement("div");
    notification.classList.add("notification", type);
    notification.innerText = message;

    notificationContainer.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.style.opacity = "0";
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

async function fetchProjectDetails(projectId) {
    try {
        const response = await fetch('view_project_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `project_id=${projectId}`
        });
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // View Button Functionality
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', async function () {
            try {
                const projectId = this.getAttribute('data-project-id');
                const modal = document.getElementById('viewProjectModal');

                const projectData = await fetchProjectDetails(projectId);
                console.log('Project Data:', projectData);

                if (projectData && projectData.employees) {
                    document.getElementById('projectTitle').textContent = projectData.title;
                    document.getElementById('teamLeaderName').textContent = projectData.team_leader_name;
                    displayEmployeeList(projectData.employees);
                    createPieChart(projectData.employees);
                    createBarChart(projectData.employees);
                    modal.classList.add('show');
                } else {
                    console.error('Invalid project data:', projectData);
                    showNotification('Error loading project details');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading project details');
            }
        });
    });

    // Add Task Button Functionality
    document.querySelectorAll('.add-task-btn').forEach(button => {
        button.addEventListener('click', async function () {
            const projectId = this.getAttribute('data-project-id');
            const projectTitle = this.getAttribute('data-project-title');
            const modal = document.getElementById('taskModal');

            document.getElementById('taskProjectId').value = projectId;
            document.getElementById('projectTitleForTask').textContent = projectTitle;

            try {
                const response = await fetch('fetch_project_employees.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `project_id=${projectId}`
                });
                const employees = await response.json();

                const employeesDropdown = document.getElementById('taskEmployees');
                employeesDropdown.innerHTML = '';
                employees.forEach(emp => {
                    const option = new Option(emp.name, emp.id);
                    employeesDropdown.appendChild(option);
                });

                // Initialize Select2
                $(employeesDropdown).select2({
                    dropdownParent: modal
                });

                modal.classList.add('show');
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading employees');
            }
        });
    });

    // Modal Close Functionality
    document.querySelectorAll('.close-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.modal').classList.remove('show');
        });
    });

    // Close on outside click
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
        }
    };

    // Task Form Submission
    // document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
    //     e.preventDefault();
    //     try {
    //         const response = await fetch('add_task.php', {
    //             method: 'POST',
    //             body: new FormData(this)
    //         });
    //         const result = await response.text();
    //         showNotification(result);
    //         if (result.includes('successfully')) {
    //             location.reload();
    //         }
    //     } catch (error) {
    //         console.error('Error:', error);
    //         showNotification('Error creating task');
    //     }
    // });

    document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const response = await fetch('add_task.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json(); //PHP TO JSON

            // Close the modal 
            document.getElementById('taskModal').classList.remove('show'); // or style.display = 'none';

            if (result.success) {
                showNotification(result.message || 'Task added successfully!', 'success');

                // Clear the form
                this.reset();

                // Reload after 2 seconds to show new task
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification(result.error || 'Error creating task!', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error creating task!', 'error');
        }
    });

    // External Notification System Function
    function showNotification(message, type) {
        const notificationContainer = document.getElementById('notificationContainer');

        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        notification.innerText = message;

        notificationContainer.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500); // Wait for fade-out before removing
        }, 3000);
    }

    function createPieChart(employees) {
        const ctx = document.getElementById('pieChart').getContext('2d');

        if (window.taskDistributionChart) {
            window.taskDistributionChart.destroy();
        }

        const data = {
            labels: employees.map(emp => emp.name),
            datasets: [{
                data: employees.map(emp => emp.total_tasks),
                backgroundColor: employees.map((_, idx) =>
                    `hsl(${(idx * 360) / employees.length}, 70%, 60%)`
                )
            }]
        };

        window.taskDistributionChart = new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    function createBarChart(employees) {
        const ctx = document.getElementById('barChart').getContext('2d');

        if (window.taskCompletionChart) {
            window.taskCompletionChart.destroy();
        }

        const data = {
            labels: employees.map(emp => emp.name),
            datasets: [
                {
                    label: 'Completed Tasks',
                    data: employees.map(emp => emp.completed_tasks),
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                },
                {
                    label: 'Pending Tasks',
                    data: employees.map(emp => emp.pending_tasks),
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                }
            ]
        };

        window.taskCompletionChart = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function displayEmployeeList(employees) {
        if (!Array.isArray(employees)) {
            console.error('Invalid employees data:', employees);
            return;
        }

        const employeeList = document.getElementById('employeeList');
        employeeList.innerHTML = '';

        employees.forEach(emp => {
            const card = document.createElement('div');
            card.className = 'employee-card';
            card.innerHTML = `
            <strong>${emp.name}</strong><br>
            Total Tasks: ${emp.total_tasks}<br>
            Completed: ${emp.completed_tasks}<br>
            Pending: ${emp.pending_tasks}
        `;
            employeeList.appendChild(card);
        });
    }
});

// Task status update functionality
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('start-btn') || e.target.classList.contains('complete-btn')) {
        const taskId = e.target.getAttribute('data-task-id');
        const newStatus = e.target.classList.contains('start-btn') ? 'In Progress' : 'Completed';

        updateTaskStatus(taskId, newStatus, e.target);
        setTimeout(() => {
            window.location.reload();
        }, 2000); // 1 second delay
        
        
    }
});

// Ensure updates also apply to the "Tasks I Have Assigned" section
async function updateTaskStatus(taskId, newStatus, button) {
    try {
        const response = await fetch('update_task_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `task_id=${taskId}&status=${encodeURIComponent(newStatus)}`
        });

        const result = await response.json();

        if (result.success) {
            const taskItem = button.closest('.task-item');
            const statusSpan = taskItem.querySelector('.task-status');
            statusSpan.textContent = `(${newStatus})`;
            

            // Update data-status attribute
            taskItem.setAttribute('data-status', newStatus);

            // Update button
            if (newStatus === 'In Progress') {
                button.textContent = 'Complete';
                button.classList.remove('start-btn');
                button.classList.add('complete-btn');
            } else if (newStatus === 'Completed') {
                button.remove();
            }

            // Handle visibility based on current filter
            const activeFilter = document.querySelector('.filter-btn.active');
            const currentFilter = activeFilter.getAttribute('data-filter');

            if (currentFilter !== 'all' && currentFilter !== newStatus) {
                taskItem.style.display = 'none';
            } else {
                taskItem.style.display = '';
            }

            showNotification(`Task status updated to ${newStatus}`);
        } else {
            showNotification(result.error || 'Failed to update task status');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error updating task status');
    }
}



document.addEventListener('DOMContentLoaded', function () {
    function setupFilters(filterButtonsSelector, taskListSelector) {
        const filterButtons = document.querySelectorAll(filterButtonsSelector);
        const taskList = document.querySelector(taskListSelector);

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                const filter = button.getAttribute('data-filter');

                // Remove "active" class only from buttons in the same section
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                // Apply filter only to tasks in the related section
                taskList.querySelectorAll('.task-item').forEach(task => {
                    const taskStatus = task.getAttribute('data-status');
                    if (filter === 'all' || filter === taskStatus) {
                        task.style.display = 'flex';
                    } else {
                        task.style.display = 'none';
                    }
                });
            });
        });
    }

    // Setup independent filters for both task sections
    setupFilters('.filter-btn-assigned-to-me', '#tasks-assigned-to-me');
    setupFilters('.filter-btn-assigned-by-me', '#tasks-assigned-by-me');
});

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".add-employee-btn").forEach(button => {
        button.addEventListener("click", async function () {
            const projectId = this.getAttribute("data-project-id");
            const projectTitle = this.getAttribute("data-project-title");
            const modal = document.getElementById("addEmployeeModal");
            const submitButton = document.querySelector('#assignEmployeesForm .submit-btn'); // Select submit button

            document.getElementById("employeeProjectId").value = projectId;
            document.getElementById("projectTitleForEmployees").textContent = projectTitle;

            try {
                const response = await fetch('fetch_available_employees.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `project_id=${projectId}`
                });
                const data = await response.json();

                // Check if there are no available employees
                if (data.all_employees.length === 0) {
                    const recommendedContainer = document.getElementById('recommendedEmployees');
                    recommendedContainer.innerHTML = '<p>No available employees to assign.</p>';
                    const availableContainer = document.getElementById('availableEmployees');
                    availableContainer.innerHTML = '<p>No available employees to assign.</p>';
                    submitButton.style.display = "none"; // Hide the button

                } else {
                    // Display recommended employees
                    const recommendedContainer = document.getElementById('recommendedEmployees');
                    recommendedContainer.innerHTML = data.recommended.map(emp => `
                        <div class="recommended-employee-card">
                            <strong>${emp.name}</strong>
                            <div class="task-count">Total Tasks: ${emp.total_tasks}</div>
                            <div>⭐ Recommended due to low workload</div>
                        </div>
                    `).join('');

                    // Display all available employees
                    const availableContainer = document.getElementById('availableEmployees');
                    availableContainer.innerHTML = data.all_employees.map(emp => `
                        <div class="employee-select-card">
                            <input type="checkbox" name="employee_ids[]" value="${emp.id}">
                            <div>
                                <strong>${emp.name}</strong>
                                <div class="task-count">Total Tasks: ${emp.total_tasks}</div>
                            </div>
                        </div>
                    `).join('');

                    submitButton.style.display = "block";
                }

                modal.style.display = "block";
            } catch (error) {
                console.error("Error:", error);
                showNotification("Error loading employees");
            }
        });
    });

    // Handle employee assignment form submission dynamically
    document.getElementById("assignEmployeesForm").addEventListener("submit", async function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        const projectId = document.getElementById("employeeProjectId").value;

        try {
            const response = await fetch("assign_employees.php", {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showNotification("Employees assigned successfully!", "success");

                // Hide the modal after assignment
                document.getElementById("addEmployeeModal").style.display = "none";

                // Update employee list dynamically without reloading
                updateEmployeeList(projectId);
            } else {
                showNotification("Error assigning employees: " + result.error, "error");
            }
        } catch (error) {
            console.error("Error:", error);
            showNotification("Error assigning employees!", "error");
        }
    });

    // Close the modal when clicking (X) or outside
    const addEmployeeModal = document.getElementById("addEmployeeModal");
    const addEmployeeCloseBtn = addEmployeeModal.querySelector(".close-btn");

    addEmployeeCloseBtn.addEventListener("click", function () {
        addEmployeeModal.style.display = "none";
    });

    window.addEventListener("click", function (event) {
        if (event.target === addEmployeeModal) {
            addEmployeeModal.style.display = "none";
        }
    });

    // Function to update employee list dynamically
    async function updateEmployeeList(projectId) {
        try {
            const response = await fetch("fetch_project_employees.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `project_id=${projectId}`
            });
            const employees = await response.json();

            // Update the employee list in the "View Project" modal
            const employeeList = document.getElementById("employeeList");
            employeeList.innerHTML = employees.map(emp => `
                <div class="employee-card">
                    <strong>${emp.name}</strong><br>
                    Total Tasks: ${emp.total_tasks}<br>
                    Completed: ${emp.completed_tasks}<br>
                    Pending: ${emp.pending_tasks}
                </div>
            `).join("");

            showNotification("Employee list updated!", "success");
        } catch (error) {
            console.error("Error fetching updated employees:", error);
        }
    }
});
