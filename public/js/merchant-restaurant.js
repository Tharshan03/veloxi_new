document.addEventListener('DOMContentLoaded', function () {
    const burger = document.querySelector('[data-merchant-burger]');
    const menu = document.querySelector('[data-merchant-menu]');

    if (burger && menu) {
        burger.addEventListener('click', function () {
            const isOpen = menu.classList.toggle('is-open');
            burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-scroll-target]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            const target = document.querySelector(trigger.getAttribute('data-scroll-target'));
            if (target) {
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    const search = document.querySelector('[data-product-search]');
    const products = document.querySelectorAll('[data-product-card]');
    if (search && products.length) {
        search.addEventListener('input', function () {
            const term = search.value.trim().toLowerCase();
            products.forEach(function (card) {
                const haystack = card.getAttribute('data-product-card').toLowerCase();
                card.style.display = haystack.includes(term) ? '' : 'none';
            });
        });
    }

    const categoryButtons = document.querySelectorAll('[data-category-target]');
    categoryButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            categoryButtons.forEach(function (item) { item.classList.remove('is-active'); });
            button.classList.add('is-active');
            const target = document.querySelector(button.getAttribute('data-category-target'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    document.querySelectorAll('.vr-alert').forEach(function (alert) {
        window.setTimeout(function () {
            alert.classList.add('is-leaving');
            window.setTimeout(function () {
                alert.remove();
            }, 260);
        }, 2600);
    });

    document.querySelectorAll('[data-qty-control]').forEach(function (form) {
        const input = form.querySelector('.vr-qty-input');
        const minus = form.querySelector('[data-qty-minus]');
        const plus = form.querySelector('[data-qty-plus]');

        function submitQuantity(nextValue) {
            if (!input) {
                return;
            }

            const min = Number(input.min || 0);
            const max = Number(input.max || 99);
            input.value = String(Math.min(max, Math.max(min, nextValue)));
            form.submit();
        }

        if (minus) {
            minus.addEventListener('click', function () {
                submitQuantity(Number(input.value || 0) - 1);
            });
        }

        if (plus) {
            plus.addEventListener('click', function () {
                submitQuantity(Number(input.value || 0) + 1);
            });
        }

        if (input) {
            input.addEventListener('change', function () {
                submitQuantity(Number(input.value || 0));
            });
        }
    });

    function formatEuro(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(Number(amount || 0));
    }

    function bumpCart(element) {
        if (!element) {
            return;
        }

        element.classList.remove('vr-cart-bump');
        void element.offsetWidth;
        element.classList.add('vr-cart-bump');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateOrderSidebar(cart) {
        const sidebarContent = document.querySelector('[data-order-sidebar-content]');

        if (!sidebarContent) {
            return;
        }

        const items = Array.isArray(cart.items) ? cart.items : [];

        if (!items.length) {
            sidebarContent.innerHTML = `
                <div class="vr-empty-cart" data-empty-cart>
                    <div style="font-size:38px;" aria-hidden="true">🛍️</div>
                    <p>Votre panier est vide.</p>
                </div>
            `;
            return;
        }

        const itemHtml = items.map(function (item) {
            return `
                <div class="vr-mini-item">
                    <div class="vr-mini-thumb" aria-hidden="true">🥙</div>
                    <div>
                        <div class="vr-mini-name">${escapeHtml(item.name)}</div>
                        <div class="vr-mini-qty">x${Number(item.quantity || 0)}</div>
                    </div>
                    <div class="vr-mini-price">${formatEuro(item.line_total)}</div>
                </div>
            `;
        }).join('');

        sidebarContent.innerHTML = `
            ${itemHtml}
            <hr style="border:0;border-top:1px solid var(--veloxi-border);margin:18px 0;">
            <div class="vr-summary-line">
                <span>Sous-total</span>
                <strong>${formatEuro(cart.subtotal)}</strong>
            </div>
            <div class="vr-summary-line">
                <span>Livraison</span>
                <strong>à calculer</strong>
            </div>
            <div class="vr-total-line">
                <span>Total</span>
                <strong>${formatEuro(cart.total || cart.subtotal)}</strong>
            </div>
            <a href="/cart" class="vr-btn vr-btn-primary">Voir le panier <span aria-hidden="true">→</span></a>
        `;
    }

    function updateCartUi(cart) {
        const count = Number(cart.count || 0);
        const cartLink = document.querySelector('[data-cart-link]');
        const countNodes = document.querySelectorAll('[data-cart-count]');
        const badge = document.querySelector('[data-cart-badge]');
        const mobileCart = document.querySelector('[data-mobile-cart]');
        const mobileCount = document.querySelector('[data-mobile-cart-count]');
        const mobileTotal = document.querySelector('[data-mobile-cart-total]');

        countNodes.forEach(function (node) {
            node.textContent = String(count);
        });

        if (badge) {
            badge.textContent = String(count);
            badge.classList.toggle('is-hidden', count <= 0);
        }

        if (mobileCart) {
            mobileCart.classList.toggle('is-hidden', count <= 0);
        }

        if (mobileCount) {
            mobileCount.textContent = String(count);
        }

        if (mobileTotal) {
            mobileTotal.textContent = formatEuro(cart.total || cart.subtotal || 0);
        }

        updateOrderSidebar(cart);
        bumpCart(cartLink);
        bumpCart(mobileCart);
    }

    document.querySelectorAll('form[action*="/cart/products/"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.innerHTML : null;

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = 'Ajout...';
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw response;
                    }

                    return response.json();
                })
                .then(function (payload) {
                    if (payload.cart) {
                        updateCartUi(payload.cart);
                    }
                })
                .catch(function () {
                    form.submit();
                })
                .finally(function () {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    }
                });
        });
    });
});
