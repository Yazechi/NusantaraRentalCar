// Filter JavaScript
document.getElementById("filterForm").addEventListener("submit", function(e){
    e.preventDefault();

    const params = new URLSearchParams(new FormData(this));

    fetch("api/filter.php?" + params.toString())
        .then(res => res.json())
        .then(data => renderCars(data));
});

function renderCars(cars){
    let html = "";

    cars.forEach(car => {
        html += `
        <div class="col-md-4 mb-3">
            <div class="card">
                <img src="assets/images/cars/${car.image}" class="card-img-top">
                <div class="card-body">
                    <h5>${car.brand_name} ${car.model}</h5>
                    <p>$${car.price_per_day}/day</p>
                    <a href="car-detail.php?id=${car.id}" class="btn btn-primary">View</a>
                </div>
            </div>
        </div>`;
    });

    document.getElementById("carsContainer").innerHTML = html;
}
