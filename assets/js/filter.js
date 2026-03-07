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
        // Add cache busting
        params.append('_t', new Date().getTime());
        
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
            
            const discount = parseInt(car.discount_percent) || 0;
            const originalPrice = parseInt(car.price_per_day);
            const discountedPrice = discount > 0 ? Math.round(originalPrice * (1 - discount / 100)) : originalPrice;
            
            const discountBadge = discount > 0 
                ? `<span class="discount-badge"><i class="fas fa-bolt me-1"></i>${discount}% OFF</span>` 
                : '';
            
            const priceHtml = discount > 0
                ? `<span class="price-original">Rp ${new Intl.NumberFormat('id-ID').format(originalPrice)}</span> <span class="price-discounted">Rp ${new Intl.NumberFormat('id-ID').format(discountedPrice)}</span> <small class="text-muted fw-normal">/ day</small>`
                : `<span class="price-normal">Rp ${new Intl.NumberFormat('id-ID').format(originalPrice)}</span> <small class="text-muted fw-normal">/ day</small>`;
            
            const isAvailable = parseInt(car.available_stock) > 0;
            const availabilityBadge = isAvailable 
                ? `<span class="badge bg-success shadow-sm"><i class="fas fa-check-circle me-1"></i>${lang.available}</span>`
                : `<span class="badge bg-danger shadow-sm"><i class="fas fa-times-circle me-1"></i>${lang.unavailable}</span>`;

            // Star rating display
            const avgRating = parseFloat(car.avg_rating) || 0;
            const reviewCount = parseInt(car.review_count) || 0;
            let starsHtml = '';
            if (reviewCount > 0) {
                starsHtml = `<div class="text-warning mb-2 small">`;
                for (let i = 1; i <= 5; i++) {
                    starsHtml += `<i class="${i <= Math.round(avgRating) ? 'fas' : 'far'} fa-star"></i>`;
                }
                starsHtml += ` <span class="text-muted">(${reviewCount})</span></div>`;
            } else {
                starsHtml = `<div class="text-muted mb-2 small"><i class="far fa-star me-1"></i>New Car</div>`;
            }

            html += `
            <div class="col-md-4 mb-4">
                <div class="deal-card h-100 shadow-sm ${!isAvailable ? 'opacity-75' : ''}">
                    <div class="deal-image">
                        <img src="${imageUrl}" alt="${car.brand_name} ${car.name}">
                        ${discountBadge}
                        <div style="position:absolute;top:10px;right:10px;">
                            ${availabilityBadge}
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-1">${car.brand_name} ${car.name}</h5>
                        ${starsHtml}
                        <p class="text-muted mb-2">
                            <small>
                                <i class="fas fa-cog me-1"></i>${car.transmission.charAt(0).toUpperCase() + car.transmission.slice(1)} |
                                <i class="fas fa-users ms-2 me-1"></i>${car.seats} seats |
                                ${car.type_name ? '<i class="fas fa-car-side me-1"></i>' + car.type_name + ' | ' : ''}
                                ${car.color ? '<i class="fas fa-palette me-1"></i>' + car.color : ''}
                            </small>
                        </p>
                        <p class="mb-3">${priceHtml}</p>
                        <a href="car-detail.php?id=${car.id}" class="btn ${isAvailable ? 'btn-primary' : 'btn-outline-secondary'} w-100">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                    </div>
                </div>
            </div>`;
        });
    }

    document.getElementById("carsContainer").innerHTML = html;
}
