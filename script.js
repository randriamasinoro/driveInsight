document.getElementById('reservationForm').addEventListener('submit', function(event) {
    var dateDebut = new Date(document.getElementById('dateDebut').value);
    var dateFin = new Date(document.getElementById('dateFin').value);
    var errorMessage = document.getElementById('errorMessage');

    if (dateDebut >= dateFin) {
        event.preventDefault();
        errorMessage.textContent = 'La date de début doit être antérieure à la date de fin.';
        errorMessage.style.display = 'block';
    } else {
        errorMessage.style.display = 'none';
    }
});
