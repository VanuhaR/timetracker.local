// =============================================================
//             ЛОГИКА РАБОТЫ ФОРМЫ ДОБАВЛЕНИЯ ОТПУСКА
// =============================================================

document.addEventListener('DOMContentLoaded', () => {

    // --- Элементы на странице ---
    const modal = document.getElementById('vacation-modal');
    const addBtn = document.getElementById('add-vacation-btn');
    const closeBtn = document.querySelector('.close-btn');
    const vacationForm = document.getElementById('vacation-form');
    const employeeSelect = document.getElementById('employee-select');
    const messageBox = document.getElementById('message-box');

    // --- Открытие/Закрытие окна ---
    addBtn.onclick = () => modal.style.display = 'block';
    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => { if (event.target == modal) modal.style.display = 'none'; };

    // --- Загрузка списка сотрудников в форму ---
    // ИСПРАВЛЕННЫЙ ПУТЬ
    fetch('../api_employees.php').then(res => res.json()).then(employees => {
        employeeSelect.innerHTML = '<option value="" disabled selected>Выберите сотрудника...</option>';
        employees.forEach(emp => {
            employeeSelect.innerHTML += `<option value="${emp.id}">${emp.full_name}</option>`;
        });
    }).catch(error => console.error('Ошибка загрузки сотрудников:', error)); // Добавим обработку ошибки

    // --- Отправка формы ---
    vacationForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(vacationForm);

        // ИСПРАВЛЕННЫЙ ПУТЬ
        fetch('../api_add_vacation.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            showMessage(data.message, data.status);
            if (data.status === 'success') {
                modal.style.display = 'none';
                vacationForm.reset();
                // ПЕРЕРИСОВЫВАЕМ КАЛЕНДАРЬ С НОВЫМИ ДАННЫМИ
                // ИСПРАВЛЕННЫЙ ПУТЬ
                fetch('../api_vacations.php').then(res => res.json()).then(newVacations => {
                    allVacations = newVacations;
                    renderHeatmap(newVacations);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Произошла ошибка.', 'error');
        });
    });

    // Функция для показа сообщений
    function showMessage(text, type) {
        messageBox.textContent = text;
        messageBox.className = `message-box ${type}`;
        messageBox.style.display = 'block';
        setTimeout(() => { messageBox.style.display = 'none'; }, 5000);
    }
});