let selectedSeats = [];
const pricePerSeat = 75000;

document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("seat")) return;
    if (e.target.classList.contains("booked")) return;

    const seat = e.target.dataset.seat;

    e.target.classList.toggle("selected");

    if (selectedSeats.includes(seat)) {
        selectedSeats = selectedSeats.filter(s => s !== seat);
    } else {
        selectedSeats.push(seat);
    }

    document.getElementById("seat-input").value = selectedSeats.join(",");
    document.getElementById("total").innerText =
        (selectedSeats.length * pricePerSeat).toLocaleString("vi-VN") + " đ";
});
