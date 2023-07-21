<?php
$main_title = get_sub_field('main_title');
$addresses_list = get_sub_field('addresses_list');
$schedule_title = get_sub_field('schedule_title');
$schedule_list = get_sub_field('schedule_list');
?>
<section class="contact-information">
    <div class="container">
        <div class="holder">
            <?php if ($main_title) echo '<h2>'.$main_title.'</h2>'; ?>
            <div class="row">
                <?php if ($addresses_list): ?>
                    <?php foreach ($addresses_list as $address): ?>
                        <div class="col-md-4 col-xl-3">
                            <?php if (!empty($address['title'])) echo '<h3>'.$address['title'].'</h3>'; ?>
                            <?php if (!empty($address['list'])): $list = $address['list']; ?>
                                <ul class="list-unstyled contact-list">
                                    <?php foreach ($list as $li): ?>
                                        <li>
                                            <?php
                                                if (!empty($li['url'])) echo '<a href="'.$li['url'].'">';
                                                if (!empty($li['icon'])) echo '<i class="material-icons-outlined">'.$li['icon'].'</i>';
                                                echo $li['text'];
                                                if (!empty($li['url'])) echo '</a>'; 
                                            ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="col-md-4 col-xl-3 ml-xl-auto schedule-col">
                    <div class="schedule-wrapp">
                        <?php if ($schedule_title) echo '<h3>'.$schedule_title.'</h3>'; ?>
                        <?php if ($schedule_list): ?>
                            <ul class="list-unstyled schedule-list">
                                <?php foreach ($schedule_list as $li): ?>
                                    <li>
                                        <?php echo $li['text']; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>