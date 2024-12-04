<?php
/**
 * @var int $total
 * @var int $page
 * @var int $pageSize
 */

$pagesCnt = @ceil($total/$pageSize);
if ($pagesCnt < 2) {
  return;
}
$pagePrev = $page - 1;
if ($pagePrev < 1) {
  $pagePrev = false;
}
$pageNext = $page + 1;
if ($pageNext > $pagesCnt) {
  $pageNext = false;
}

$pagerStart = 1;
$pagerFinish = $pagesCnt;
if ($pagesCnt > 6) {
  $pagerStart = max(1, $page - 3);
  $pagerFinish = min($pagesCnt, $page + 3);
}
?>
<div class="row my-3">
  <div class="col-md-8 offset-md-2 text-center">
    <div class="my-1">Всего: <?=$total?></div>
    <nav>
      <ul class="pagination justify-content-center">

        <li class="page-item<?=($pagePrev ? false : ' disabled')?>">
          <?php
          $href = Utils::qs(['page'=>($pagePrev > 1 ? $pagePrev : false)])
          ?>
          <a class="page-link"<?=($pagePrev ? " href=\"{$href}\"" : false)?>><span>&laquo;</span></a>
        </li>

      <?php
      for ($i = $pagerStart; $i <= $pagerFinish; $i++) {
        $active = ($i === $page);
        $href = Utils::qs(['page'=>($i > 1 ? $i : false)]);
        ?>
        <li class="page-item<?=($active ? ' active' : false)?>">
          <a class="page-link"<?=($active ? false : " href=\"{$href}\"" )?>><?=$i?></a>
        </li>
      <?php
      }
      ?>

        <li class="page-item<?=($pageNext ? false : ' disabled')?>">
          <?php
          $href = Utils::qs(['page'=>($pageNext > 1 ? $pageNext : false)]);
          ?>
          <a class="page-link"<?=($pageNext ? " href=\"{$href}\"" : false)?>><span>&raquo;</span></a>
        </li>
      </ul>
    </nav>
  </div>
</div>
