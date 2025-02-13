let allData = null;

let selectedAttributesTree = ["title", "description", "status", "deadline", "priority"];
let selectedAttributesKanban = ["title", "status", "deadline", "priority"];

let rearrangeMode = false;
let pendingMoveCard = null;
let newStatus = null;
let newEmployeeId = null;

document.addEventListener('DOMContentLoaded', () => {
  fetchDataAndRender();
  setInterval(() => {
    fetchDataAndRender();
  }, 5000);
  document.getElementById('applyFiltersTreeBtn').addEventListener('click', () => {
    const filterSelect = document.getElementById('attributeFilterTree');
    selectedAttributesTree = Array.from(filterSelect.selectedOptions).map(o => o.value);
    renderProjectTree();
  });
  document.getElementById('applyFiltersKanbanBtn').addEventListener('click', () => {
    const filterSelect = document.getElementById('attributeFilterKanban');
    selectedAttributesKanban = Array.from(filterSelect.selectedOptions).map(o => o.value);
    renderKanbanBoard();
  });
  const rearrangeBtn = document.getElementById('rearrangeBtn');
  rearrangeBtn.addEventListener('click', () => {
    rearrangeMode = !rearrangeMode;
    rearrangeBtn.textContent = rearrangeMode ? "Exit Rearrange Mode" : "Rearrange";
    toggleDragAndDrop(rearrangeMode);
  });
  document.getElementById('confirmYes').addEventListener('click', confirmMoveYes);
  document.getElementById('confirmNo').addEventListener('click', confirmMoveNo);
});

function fetchDataAndRender() {
  fetch('fetch_data.php')
    .then(res => res.json())
    .then(data => {
      allData = data;
      renderProjectTree();
      renderKanbanBoard();
    })
    .catch(err => console.error('Fetch error:', err));
}

function renderProjectTree() {
  const svg = document.getElementById('projectTreeSVG');
  svg.innerHTML = '';
  if (!allData || !allData.tasks) return;
  const tasks = allData.tasks;
  const deps = allData.dependencies;
  const cardWidth = 300;
  const cardHeight = 150;
  let xOffset = 50;
  let yOffset = 50;
  let positions = {};
  tasks.forEach((task, idx) => {
    const x = xOffset;
    const y = yOffset + idx * 180;
    const cardDiv = document.createElementNS("http://www.w3.org/2000/svg", "foreignObject");
    cardDiv.setAttribute("x", x);
    cardDiv.setAttribute("y", y);
    cardDiv.setAttribute("width", cardWidth);
    cardDiv.setAttribute("height", cardHeight);
    const cardContent = document.createElement("div");
    cardContent.className = "task-card";
    const lines = buildTaskDisplayLines(task, selectedAttributesTree);
    lines.forEach(lineText => {
      const lineDiv = document.createElement("div");
      lineDiv.className = "task-section";
      lineDiv.textContent = lineText;
      cardContent.appendChild(lineDiv);
    });
    cardDiv.appendChild(cardContent);
    svg.appendChild(cardDiv);
    positions[task.id] = {
      x: x,
      y: y,
      width: cardWidth,
      height: cardHeight
    };
  });
  deps.forEach(d => {
    const fromPos = positions[d.depends_on_task_id];
    const toPos = positions[d.task_id];
    if (!fromPos || !toPos) return;
    const fromCenterX = fromPos.x + fromPos.width / 2;
    const fromBottomY = fromPos.y + fromPos.height;
    const toCenterX = toPos.x + toPos.width / 2;
    const toTopY = toPos.y;
    const line1 = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line1.setAttribute("x1", fromCenterX);
    line1.setAttribute("y1", fromBottomY);
    line1.setAttribute("x2", fromCenterX);
    line1.setAttribute("y2", toTopY);
    line1.setAttribute("stroke", "#000");
    line1.setAttribute("stroke-width", "2");
    svg.appendChild(line1);
    const line2 = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line2.setAttribute("x1", fromCenterX);
    line2.setAttribute("y1", toTopY);
    line2.setAttribute("x2", toCenterX);
    line2.setAttribute("y2", toTopY);
    line2.setAttribute("stroke", "#000");
    line2.setAttribute("stroke-width", "2");
    svg.appendChild(line2);
  });
}

function renderKanbanBoard() {
  const kanbanContainer = document.getElementById('kanbanBoard');
  kanbanContainer.innerHTML = '';
  if (!allData || !allData.tasks) return;
  const assignedRowContainer = document.createElement('div');
  assignedRowContainer.className = 'kanban-row-container';
  const overdueRowContainer = document.createElement('div');
  overdueRowContainer.className = 'kanban-row-container';
  allData.employees.forEach(emp => {
    const assignedCol = document.createElement('div');
    assignedCol.className = 'kanban-column';
    assignedCol.dataset.employeeId = emp.id;
    assignedCol.dataset.sectionType = 'assigned';
    const assignedHeader = document.createElement('h3');
    assignedHeader.textContent = `${emp.name} - Assigned`;
    assignedCol.appendChild(assignedHeader);
    const assignedTasksDiv = document.createElement('div');
    assignedTasksDiv.className = 'kanban-task-list';
    const empTasks = allData.assignments
      .filter(a => a.employee_id === emp.id)
      .map(a => a.task_id);
    allData.tasks.forEach(task => {
      if (!empTasks.includes(task.id)) return;
      const deadline = new Date(task.deadline);
      const today = new Date();
      const isOverdue = (deadline < today);
      if (!isOverdue) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'task-card';
        if (rearrangeMode) cardDiv.draggable = true;
        cardDiv.addEventListener('dragstart', onDragStart);
        const sections = selectedAttributesKanban.map(attr => {
          if (task.hasOwnProperty(attr)) {
            return `<div class="task-section"><strong>${attr}:</strong> ${task[attr]}</div>`;
          }
          return '';
        }).join('');
        cardDiv.innerHTML = sections;
        cardDiv.dataset.taskId = task.id;
        cardDiv.dataset.employeeId = emp.id;
        assignedTasksDiv.appendChild(cardDiv);
      }
    });
    assignedCol.appendChild(assignedTasksDiv);
    assignedRowContainer.appendChild(assignedCol);
    const overdueCol = document.createElement('div');
    overdueCol.className = 'kanban-column';
    overdueCol.dataset.employeeId = emp.id;
    overdueCol.dataset.sectionType = 'overdue';
    const overdueHeader = document.createElement('h3');
    overdueHeader.textContent = `${emp.name} - Overdue`;
    overdueCol.appendChild(overdueHeader);
    const overdueTasksDiv = document.createElement('div');
    overdueTasksDiv.className = 'kanban-task-list';
    allData.tasks.forEach(task => {
      if (!empTasks.includes(task.id)) return;
      const deadline = new Date(task.deadline);
      const today = new Date();
      if (deadline < today) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'task-card overdue';
        if (rearrangeMode) cardDiv.draggable = true;
        cardDiv.addEventListener('dragstart', onDragStart);
        const sections = selectedAttributesKanban.map(attr => {
          if (task.hasOwnProperty(attr)) {
            return `<div class="task-section"><strong>${attr}:</strong> ${task[attr]}</div>`;
          }
          return '';
        }).join('');
        cardDiv.innerHTML = sections;
        cardDiv.dataset.taskId = task.id;
        cardDiv.dataset.employeeId = emp.id;
        overdueTasksDiv.appendChild(cardDiv);
      }
    });
    overdueCol.appendChild(overdueTasksDiv);
    overdueRowContainer.appendChild(overdueCol);
  });
  kanbanContainer.appendChild(assignedRowContainer);
  kanbanContainer.appendChild(overdueRowContainer);
  const dropTargets = kanbanContainer.querySelectorAll('.kanban-column, .kanban-task-list');
  dropTargets.forEach(target => {
    target.addEventListener('dragover', onDragOver);
    target.addEventListener('drop', onDrop);
  });
}

function buildTaskDisplayLines(task, attributes) {
  let lines = [];
  attributes.forEach(attr => {
    if (task.hasOwnProperty(attr)) {
      lines.push(`${attr}: ${task[attr]}`);
    }
  });
  return lines;
}

function toggleDragAndDrop(enable) {
  const cards = document.querySelectorAll('.task-card');
  cards.forEach(card => {
    if (enable) {
      card.setAttribute('draggable', 'true');
    } else {
      card.removeAttribute('draggable');
    }
  });
}

function onDragStart(e) {
  const taskId = e.target.dataset.taskId;
  const employeeId = e.target.dataset.employeeId;
  e.dataTransfer.setData("text/plain", JSON.stringify({ taskId, employeeId }));
}

function onDragOver(e) {
  e.preventDefault();
}

function onDrop(e) {
  e.preventDefault();
  if (!rearrangeMode) return;
  const data = JSON.parse(e.dataTransfer.getData("text/plain"));
  pendingMoveCard = data.taskId;
  let target = e.currentTarget;
  if (target.classList.contains('kanban-task-list')) {
    target = target.parentElement;
  }
  newEmployeeId = target.dataset.employeeId || null;
  const sectionType = target.dataset.sectionType || 'assigned';
  if (sectionType === 'overdue') {
    newStatus = 'Not Started';
  } else {
    newStatus = 'In Progress';
  }
  document.getElementById('confirmModal').style.display = 'flex';
}

function confirmMoveYes() {
  if (!pendingMoveCard || !newEmployeeId || !newStatus) {
    confirmMoveNo();
    return;
  }
  const formData = new FormData();
  formData.append('task_id', pendingMoveCard);
  formData.append('employee_id', newEmployeeId);
  formData.append('status', newStatus);
  fetch('update_task.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(resp => {
      document.getElementById('confirmModal').style.display = 'none';
      if (resp.success) {
        fetchDataAndRender();
      } else {
        console.error(resp.error);
      }
    })
    .catch(err => console.error(err));
}

function confirmMoveNo() {
  document.getElementById('confirmModal').style.display = 'none';
  pendingMoveCard = null;
  newEmployeeId = null;
  newStatus = null;
}
