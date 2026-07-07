(function () {
    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }

        document.addEventListener('DOMContentLoaded', callback);
    }

    ready(function () {
        var progress = document.querySelector('[data-blog-progress]');
        var article = document.querySelector('[data-blog-article]');
        var backToTop = document.querySelector('[data-blog-back-to-top]');
        var tocLinks = Array.prototype.slice.call(document.querySelectorAll('[data-blog-toc-link]'));
        var headings = tocLinks
            .map(function (link) {
                return document.getElementById((link.getAttribute('href') || '').replace('#', ''));
            })
            .filter(Boolean);

        function updateScrollUi() {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (progress && article) {
                var start = article.offsetTop;
                var end = start + article.offsetHeight - window.innerHeight;
                var percent = end > start ? ((scrollTop - start) / (end - start)) * 100 : 0;
                progress.style.width = Math.max(0, Math.min(100, percent)) + '%';
            }

            if (backToTop) {
                backToTop.classList.toggle('is-visible', scrollTop > 640);
            }

            if (tocLinks.length && headings.length) {
                var activeId = headings[0].id;
                headings.forEach(function (heading) {
                    if (heading.getBoundingClientRect().top <= 120) {
                        activeId = heading.id;
                    }
                });

                tocLinks.forEach(function (link) {
                    link.classList.toggle('is-active', link.getAttribute('href') === '#' + activeId);
                });
            }
        }

        tocLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var target = document.querySelector(link.getAttribute('href'));
                if (!target) return;
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        if (backToTop) {
            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        document.querySelectorAll('[data-blog-copy-url]').forEach(function (button) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-blog-copy-url');
                if (!url || !navigator.clipboard) return;
                navigator.clipboard.writeText(url).then(function () {
                    var previous = button.textContent;
                    button.textContent = 'Скопировано';
                    setTimeout(function () { button.textContent = previous; }, 1400);
                });
            });
        });

        updateScrollUi();
        window.addEventListener('scroll', updateScrollUi, { passive: true });
        window.addEventListener('resize', updateScrollUi);
    });
}());
