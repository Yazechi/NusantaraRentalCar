// Filter JavaScript with debouncing and request cancellation
let filterTimeout = null;
let currentController = null;

// Debounced filter function
function applyFilter() {
    // Clear any pending filter request
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }
    
    // Cancel any in-flight request
    if (currentController) {
        currentController.abort();
    }
    
    // Show loading state
    const container = document.getElementById("carsContainer");
    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Filtering cars...</p></div>';
    
    // Debounce: wait 300ms before making the request
    filterTimeout = setTimeout(() => {
        const form = document.getElementById("filterForm");
        const params = new URLSearchParams(new FormData(form));
        
        // Create new AbortController for this request
        currentController = new AbortController();
        
        fetch("api/filter.php?" + params.toString(), {
            signal: currentController.signal
        })
            .then(res => res.json())
            .then(data => {
                renderCars(data);
                currentController = null;
            })
            .catch(err => {
                // Ignore abort errors (user changed filter)
                if (err.name !== 'AbortError') {
                    console.error('Filter error:', err);
                    container.innerHTML = '<div class="col-12 text-center py-5"><div class="alert alert-danger">Error loading cars. Please try again.</div></div>';
                }
            });
    }, 300);
}

// Form submit handler
document.getElementById("filterForm").addEventListener("submit", function(e) {
    e.preventDefault();
    applyFilter();
});

// Real-time filter on input change
document.querySelectorAll('#filterForm select, #filterForm input').forEach(input => {
    input.addEventListener('change', applyFilter);
});

function renderCars(cars) {
    let html = "";

    if (cars.length === 0) {
        html = '<div class="col-12 text-center py-5"><div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No cars found matching your criteria. Please try different filters.</div></div>';
    } else {
        cars.forEach(car => {
            const imageUrl = car.image_main 
                ? `uploads/cars/${car.image_main}` 
                : 'assets/images/cars/default.png';
            
            html += `
            <div class="col-md-4 mb-4">
                <div class="card h-100 car-card shadow-sm">
                    <div class="car-image-wrapper">
                        <img src="${imageUrl}" class="card-img-top" alt="${car.brand_name} ${car.name}">
                        <div class="card-overlay">
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Available</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-primary">${car.brand_name} ${car.name}</h5>
                        <p class="text-muted mb-2">
                            <small>
                                <i class="fas fa-cog me-1"></i>${car.transmission.charAt(0).toUpperCase() + car.transmission.slice(1)} |
                                <i class="fas fa-users ms-2 me-1"></i>${car.seats} seats
                            </small>
                        </p>
                        <p class="fw-bold text-primary mb-3 fs-5">Rp ${new Intl.NumberFormat('id-ID').format(car.price_per_day)} <small class="text-muted fw-normal">/ day</small></p>
                        <a href="car-detail.php?id=${car.id}" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                    </div>
                </div>
            </div>`;
        });
    }

    document.getElementById("carsContainer").innerHTML = html;
}
