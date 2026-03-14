document.addEventListener("DOMContentLoaded", () => {
    // --- 1. GLOBAL SELECTORS ---
    const recipeForm = document.getElementById('recipe-upload-form');
    const submitModal = document.getElementById('submit-modal');
    const searchInput = document.querySelector('.search-container input');

    // --- 2. NAVIGATION: SIDEBAR & RECIPE CARDS ---
    const navMap = [
        { btn: 'btn-my-recipes', section: 'my-recipes-section' },
        { btn: 'btn-create-recipe', section: 'create-recipe-section' },
        { btn: 'btn-purchased', section: 'purchased-recipes-section' },
        { btn: 'btn-earnings', section: 'earnings-section' },
        { btn: 'btn-settings', section: 'settings-section' }
    ];

    navMap.forEach(item => {
        const button = document.getElementById(item.btn);
        const section = document.getElementById(item.section);
        if (button && section) {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.content-wrapper, .recipe-section').forEach(s => {
                    s.style.display = 'none';
                    s.classList.remove('create-recipe-active');
                });
                document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
                
                section.style.display = 'block';
                if(item.section === 'create-recipe-section') section.classList.add('create-recipe-active');
                button.classList.add('active');
            });
        }
    });

    // --- 3. UNIVERSAL IMAGE LOGIC (Now correctly using all zones) ---
    
    // This handles the "Click to browse" part for ANY zone
    document.addEventListener('click', (e) => {
        const zone = e.target.closest('.universal-drop-zone');
        if (zone) {
            if (e.target.closest('.remove-btn')) return;
            const input = zone.querySelector('input[type="file"]');
            if (input) input.click();
        }
    });

    // Initialize all zones (This uses your "unused" variable logic correctly)
    document.querySelectorAll('.universal-drop-zone').forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        if (!input) return;

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('active');
        });

        ['dragleave', 'drop'].forEach(type => {
            zone.addEventListener(type, () => zone.classList.remove('active'));
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                handleZonePreview(input.files[0], zone);
            }
        });

        input.addEventListener('change', () => {
            if (input.files.length > 0) {
                handleZonePreview(input.files[0], zone);
            }
        });
    });

    function handleZonePreview(file, zone) {
        const reader = new FileReader();
        reader.onload = (e) => {
            // Logic for Edit Mode (Existing Img)
            let img = zone.querySelector('img:not(.drop-icon)');
            
            if (img && (zone.id === 'edit-drop-zone' || zone.classList.contains('image-upload-section'))) {
                img.src = e.target.result;
                img.style.display = 'block';
                img.classList.add('img-preview-el');
            } else {
                // Logic for Create Mode (Injecting Preview)
                const dropText = zone.querySelector(".drop-text");
                const dropIcon = zone.querySelector(".drop-icon");
                if (dropText) dropText.style.display = "none";
                if (dropIcon) dropIcon.style.display = "none";

                const oldWrapper = zone.querySelector('.preview-wrapper');
                if (oldWrapper) oldWrapper.remove();

                const previewHTML = `
                    <div class="preview-wrapper">
                        <img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover; border-radius:12px;">
                        <button type="button" class="remove-btn" onclick="event.stopPropagation(); location.reload();">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>`;
                zone.insertAdjacentHTML('beforeend', previewHTML);
            }
        };
        reader.readAsDataURL(file);
    }

    // --- 4. UTILITIES (The Reset Logic) ---
    function resetAllZones() {
        document.querySelectorAll('.universal-drop-zone').forEach(zone => {
            const input = zone.querySelector('input[type="file"]');
            if (input) input.value = "";
            
            const dropText = zone.querySelector(".drop-text");
            const dropIcon = zone.querySelector(".drop-icon");
            if (dropText) dropText.style.display = "block";
            if (dropIcon) dropIcon.style.display = "block";
            
            const preview = zone.querySelector(".preview-wrapper");
            if (preview) preview.remove();
        });
    }

    // --- 5. SUBMIT MODAL & FORM LOGIC ---
    if (recipeForm && submitModal) {
        recipeForm.addEventListener('submit', function(e) {
            if (!submitModal.dataset.confirmed) {
                e.preventDefault();
                submitModal.style.display = 'flex';
            }
        });

        const sellToggle = document.getElementById('sell-toggle');
        const priceContainer = document.getElementById('price-input-container');
        const finalSubmitBtn = document.getElementById('final-submit');

        if (sellToggle) {
            sellToggle.addEventListener('change', function() {
                if (priceContainer) priceContainer.style.display = this.checked ? 'block' : 'none';
            });
        }

        if (finalSubmitBtn) {
            finalSubmitBtn.onclick = () => {
                submitModal.dataset.confirmed = "true";
                recipeForm.submit();
            };
        }

        const closeBtn = document.getElementById('close-modal');
        if (closeBtn) closeBtn.onclick = () => submitModal.style.display = 'none';
    }

    // --- 6. SETTINGS & CLEAR ---
    const btnClear = document.getElementById('btn-clear-recipe');
    if (btnClear && recipeForm) {
        btnClear.onclick = () => {
            if (confirm("Clear all fields?")) {
                recipeForm.reset();
                resetAllZones();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };
    }

    // Passwords & Search logic remains same...
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.recipe-card').forEach(card => {
                const title = card.querySelector('h4').textContent.toLowerCase();
                card.style.display = title.includes(term) ? "block" : "none";
            });
        });
    }
});