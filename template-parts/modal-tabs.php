<?php
$post_type = get_field('post_type') ? get_field('post_type') : get_queried_object()->name;
$list_taxonomies = wps_tax($post_type);
?>
<!-- Modal filter schedule -->
<div class="modal modal-filter" id="filterSchedule" tabindex="-1" aria-labelledby="filterSchedule" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Select', 'shopperexpress'); ?>
                    <span class="tab-title">
                        <?php foreach ($list_taxonomies as $index => $value) : ?>
                            <span data-id="tab-<?php echo $index; ?>"><?php echo $value['label']; ?></span>
                        <?php endforeach; ?>
                    </span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
                        <path
                        d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"
                        />
                    </svg>
                </button>
            </div>
            <div class="modal-body-wrap">
                <form action="#" class="modal-filter-form" data-template="#filters-template">
                    <div class="modal-body">
                        <div class="tab-content">
                            <?php foreach ($list_taxonomies as $index => $value) : ?>
                                <div data-id="tab-<?php echo $index; ?>">
                                    <?php if ($index == 'mileage') : ?>
                                        <div class="range-box modal-range-box">
                                            <input value="0,32500" min="0" max="32500" step="1" min-range="1000" show-tooltip="true" type="range" multiple>
                                            <input type="hidden" name="<?php echo $index; ?>" value="0,32500">
                                        </div>
                                    <?php else : ?>
                                        <ul class="modal-filter-list list-unstyled" data-filter-category="<?php echo $index; ?>"></ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
                <script id="filters-template" type="text/x-handlebars-template">
                    <li>
                    <label class="custom-check">
                        <input class="fake-input" type="checkbox" name="{{filter}}" value="{{slug}}">
                        <span class="fake-label">
                            <span class="name">{{name}}</span>
                            <span class="detail"><span class="detail-count">{{count}}</span> available</span>
                        </span>
                        <span class="fake-checkbox">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="m382-354 339-339q12-12 28-12t28 12q12 12 12 28.5T777-636L410-268q-12 12-28 12t-28-12L182-440q-12-12-11.5-28.5T183-497q12-12 28.5-12t28.5 12l142 143Z"/></svg>
                        </span>
                    </label>
                </li>
            </script>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link btn-lg btn-clear" disabled><?php esc_html_e('Clear Selections', 'shopperexpress'); ?></button>
                <button type="button" class="btn btn-primary btn-lg" data-dismiss="modal"><?php esc_html_e('View', 'shopperexpress'); ?> <span class="result-total">0</span> <span data-singular-text="match" data-plural-text="matches"></span></button>
            </div>
        </div>
    </div>
</div>