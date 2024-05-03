// Функция для получения данных из API
async function fetchData(sortField, sortType, start, limit) {
    const url = `domain.com/api.php?action=get.reqs&sort_field=${sortField}&sort_type=${sortType}&start=${start}&limit=${limit}`;
    const response = await fetch(url);
    const data = await response.json();
    return data;
}

// Функция для отображения данных в виде таблицы
function displayData(data) {
    const table = document.createElement('table');
    const thead = table.createTHead();
    const tbody = table.createTBody();
    const headers = ['ИД', 'Название юрлица', 'Название бара', 'Дата требования', 'Номер договора', 'Статус оплаты'];

    // Создание заголовков таблицы
    const headerRow = thead.insertRow();
    headers.forEach(headerText => {
        const th = document.createElement('th');
        const text = document.createTextNode(headerText);
        th.appendChild(text);
        headerRow.appendChild(th);
    });

    // Заполнение данных таблицы
    data.reqs.forEach(req => {
        const row = tbody.insertRow();
        const status = req.status === 0 ? 'Не оплачено' : (req.status === 1 ? 'Частично оплачено' : 'Оплачено полностью');
        const rowData = [req.id, data.lps[req.lp_id], data.bars[req.bar_id], req.dt, req.doc_num, status];
        rowData.forEach(cellData => {
            const cell = row.insertCell();
            const text = document.createTextNode(cellData);
            cell.appendChild(text);
        });
    });

    document.body.appendChild(table);
}

// Вызов функции для получения данных из API и отображения их в таблице
fetchData('id', 'ASC', 0, 100000)
    .then(data => displayData(data))
    .catch(error => console.error('Ошибка получения данных:', error));


// Примечание: Этот скрипт предполагает, что вы используете его в среде, где доступен объект fetch для выполнения HTTP-запросов.
// Если вы планируете использовать его в среде без этого объекта (например, Node.js), вам нужно будет использовать соответствующий модуль
// для выполнения запросов.