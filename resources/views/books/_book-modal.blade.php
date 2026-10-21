{{-- Single 3D modal reused by every shelf on the page (filled by public/js/salrc-3d.js). --}}
<div class="b3m" id="book3d-modal" role="dialog" aria-modal="true" aria-labelledby="b3m-title">
    <div class="b3m__backdrop" data-b3m-close></div>
    <div class="b3m__panel">
        <button type="button" class="b3m__close" data-b3m-close aria-label="Close">&times;</button>
        <div class="b3m__stage" data-b3m-stage></div>
        <div class="b3m__info">
            <span class="b3m__kicker" data-b3m="category"></span>
            <h3 id="b3m-title" data-b3m="title"></h3>
            <p class="b3m__author" data-b3m="author"></p>
            <span class="b3m__status" data-b3m="status"></span>
            <dl class="b3m__meta">
                <div><dt>Call no.</dt><dd data-b3m="call"></dd></div>
                <div><dt>Accession</dt><dd data-b3m="accession"></dd></div>
                <div><dt>Copies</dt><dd data-b3m="copies"></dd></div>
                <div><dt>Publisher</dt><dd data-b3m="publisher"></dd></div>
            </dl>
            <p class="b3m__summary" data-b3m="summary"></p>
            <div class="b3m__actions" data-b3m="actions"></div>
        </div>
    </div>
</div>
