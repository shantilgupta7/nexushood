function sortTable(columnIndex) {
    var table = document.getElementById("sortableTable");
    var rows = Array.from(table.rows).slice(1);
    var sortedRows = rows.sort((rowA, rowB) => {
        var cellA = rowA.cells[columnIndex].innerText.toLowerCase();
        var cellB = rowB.cells[columnIndex].innerText.toLowerCase();

        if (!isNaN(cellA) && !isNaN(cellB)) {
            return parseFloat(cellA) - parseFloat(cellB);
        }

        if (cellA < cellB) return -1;
        if (cellA > cellB) return 1;
        return 0;
    });

    table.tBodies[0].append(...sortedRows);
}
