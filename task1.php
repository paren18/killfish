
Ошибка "Undefined offset" обычно возникает, когда происходит попытка доступа к элементу массива, который не существует. В данном случае, вероятно, ошибка связана с неправильным доступом к элементам массива $products или $units, потому что некоторые элементы не определены или не инициализированы корректно.

Чтобы найти место, где возникает ошибка, можно начать с проверки строк, где происходит доступ к элементам массивов, и убедиться, что они существуют перед использованием. Вот несколько возможных мест, где могут возникать проблемы:

В строке $products[$units[$ingr_id]['egais_prod_v_code']] и $products[$prod_id]['alc_code'] - убедитесь, что $units[$ingr_id]['egais_prod_v_code'] и $prod_id существуют и корректно инициализированы.
В строке $ingrs[$ingr_id] - убедитесь, что $ingr_id существует в массиве $ingrs.
В строке $products[$prod_id]['full_name'] - убедитесь, что $prod_id существует в массиве $products и имеет ключ 'full_name'.
В строке $products[$egais_prod_id] - убедитесь, что $egais_prod_id существует в массиве $products.
Путем добавления дополнительных проверок на существование элементов массива перед их использованием можно избежать ошибки "Undefined offset" и убедиться в корректной работе кода.



<?php


# Ингредиент - сканируем марки


require($_SERVER['DOCUMENT_ROOT'] . '/../projects/config.php');
require(PROJECT_PATH . '/project.config.php');
if (!defined('KF_CONFIG_PATH')) define('KF_CONFIG_PATH', PROJECT_PATH . '/project.config.php');
if (!defined('KF_CONFIG_MODE')) define('KF_CONFIG_MODE', 'admin');
require_once(ROOT . '/projects/autoload.php');

// требуется право на редактирование ингредиентов
$kfConfig['rights'] = array('editingr');
$kfConfig['title'] = 'Привязка ингредиентов к ЕГАИС';

$kfConfig['plugins'] = array('ui', 'chosen', 'tools', 'ingrsearch');

$kfConfig['css'][] = 'css20/admin.ingr.link.egais.css';

$pdo = SPDO::get_exception();
$pdo_egais = SPDO::get_egais();
$pdo_egais->query('SET NAMES cp1251');

$admin = new AdminIngr($kfConfig);

try {


    $admin->getHeader();
    if (!$admin->page['rights_ok']) throw new AdminRightsException();
    if ($admin->page['error']) throw new AdminException();

    // но также требуем права по егаису
    $admin->throwErrorRightsAND('editingr', 'egais.adv.admin');

    $admin->page['js'][] = 'js20/admin.ingr.link.egais.js';

    // prod_id => ingr_ids

    $units = array();
    $egais_prod_ids = array();
    $products = array();
    $ingr_ids = array();
    $ingrs = array();

    $pdo->str =
        'SELECT * FROM `ingr_link_egais` ORDER BY `ingr_id` ASC, `egais_prod_alc_code` ASC, `egais_prod_v_code` ASC';
    $pdo->execute();

    $egais_prod_codes = array();

    while ($r = $pdo->fetch_assoc()) {

        $ingr_id = (int)$r['ingr_id'];
        $egais_alc_code = $r['egais_prod_alc_code'];
        $egais_prod_id = $r['egais_prod_id'];


        $ingr_ids[] = $ingr_id;
        $egais_prod_codes[] = $r['egais_prod_v_code'];

        if (!isset($units[$ingr_id])) {
            $units[$ingr_id] = $r;
            $units[$ingr_id]['products'] = array();
        }

        if ($egais_prod_id > 0) {

            // данные по продуктам егаис для отображения
            if (!isset($products[$egais_prod_id])) {

                $products[$egais_prod_id] = array('full_name' => '---', 'short_name' => '---', 'alc_code' => $egais_alc_code);

                $pdo_egais->query(
                    'SELECT `full_name`,`short_name`,`alc_code` FROM `products` WHERE `id`=:prod_id',
                    array('prod_id' => $egais_prod_id));

                if ($pdo_egais->num_rows > 0) $products[$egais_prod_id] = $pdo_egais->fetch_row();
            }

            // id => status
            $units[$ingr_id]['products'][$egais_prod_id] = (int)$r['status'];

        }
    }
    $pdo->clear();

    $ingr_ids = array_values(array_unique($ingr_ids));

    if (count($ingr_ids) > 0) {
        $pdo->query('SELECT `id`,`title` FROM `menu_ingr` WHERE `id` IN(:ids)', array('ids' => array_unique($ingr_ids)));
        $ingrs = $pdo->fetch_all_assoc('id', 'title');
    }

    $admin->addPageVars('ingr_ids', $ingr_ids);

    $egais_prod_codes = array_values(array_unique($egais_prod_codes));
    $products = array();
    if (count($egais_prod_codes) > 0) {
        $pdo_egais->query('SELECT `code`,`title` FROM `prod_codes` WHERE `code` IN(:codes)', array('codes' => $egais_prod_codes));
        $products = $pdo_egais->fetch_all_assoc('code', 'title');
    }

    //print_r($units);

    ?>

    <div class="row">
        <h1>
            <?= $kfConfig['title'] ?>
            <button class="btn btn-primary" id="add_link"><i class="icon-plus icon-white"></i> Добавить привязку
            </button>
        </h1>
    </div>

    <div class="row">
        <table class="table table-condensed table-bordered">
            <thead>
            <tr>
                <th class="bg840">ингредиент</th>
                <th class="bg840">код продукта</th>
                <th class="bg840">алкокоды</th>
                <th class="bg840">действия</th>
            </tr>
            </thead>
            <tbody id="links">

            <? foreach ($ingr_ids as $ingr_id): ?>
                <tr data-ingr="<?= $ingr_id ?>">
                    <td class="ingr_name">
                        <a href="menu.ingr.one.php?id=<?= $ingr_id ?>" target="_blank"><?= $ingrs[$ingr_id] ?></a>
                    </td>

                    // Заменяем строки, где возможно возникновение ошибки "Undefined offset", на безопасные обращения к
                    массивам

                    <td class="prod_code">
                        <span class="label prod_code"><?= isset($units[$ingr_id]['egais_prod_v_code']) ? $units[$ingr_id]['egais_prod_v_code'] : '' ?></span>
                        <span class="prod_title"><?= isset($units[$ingr_id]['egais_prod_v_code']) && isset($products[$units[$ingr_id]['egais_prod_v_code']]) ? $products[$units[$ingr_id]['egais_prod_v_code']] : '' ?></span>
                    </td>

                    <? if (count($units[$ingr_id]['products']) > 0): ?>
                        <ul>
                            <? foreach ($units[$ingr_id]['products'] as $prod_id => $status): ?>
                                <li data-id="<?= $prod_id ?>">
                <span class="label alc_code label-<? if ($status == 0): ?>gray<? else: ?>black<?endif ?>">
                    <?= isset($products[$prod_id]['alc_code']) ? $products[$prod_id]['alc_code'] : '' ?>
                </span>
                                    &nbsp;<span
                                            class="product"><?= isset($products[$prod_id]['full_name']) ? $products[$prod_id]['full_name'] : '' ?></span>
                                </li>
                            <?endforeach ?>
                        </ul>
                    <?endif ?>
                    <td>
                        <button class="btn edit"><i class="icon icon-edit"></i></button>
                        <button class="btn btn-danger delete"><i class="icon icon-remove"></i></button>
                    </td>
                </tr>


            <?endforeach ?>

            </tbody>
        </table>
    </div>


    <div class="modal hide" id="link_dialog">

        <div class="modal-header">
            <a class="close" data-dismiss="modal">&times;</a>
            <h4></h4>
        </div>

        <div class="modal-body">

            <div class="form-horizontal">


                <div class="control-group">
                    <label class="control-label b">Ингредиент</label>
                    <div class="controls">
                        <input type="text" class="input-xlarge" id="ingr"/>
                        <span id="ingr_title" class="label"></span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label b">Код продукта</label>
                    <div class="controls">
                        <input type="text" class="input-mini" id="egc"/>
                        &nbsp; <span id="code_name" class="label"></span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label b">Алкокоды</label>
                    <div class="controls">
                        <input type="text" class="input-xlarge" id="alc_codes"/>
                        <div id="alc_code_list"></div>
                    </div>
                </div>


            </div>


        </div>

        <div class="modal-footer">
            <button class="btn btn-success" id="save">Сохранить</button>
            <button class="btn" data-dismiss="modal">Отменить</button>
        </div>

    </div>


    <?php

    // ВОТ ДОСЮДА

} catch (Exception $e) {
    if ($e->getMessage()) $admin->page['error'] = $e->getMessage();
}

$admin->getFooter();

