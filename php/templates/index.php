<?php
/**
 * @var Controller $this
 * @var array $items
 * @var int $total
 * @var int $page
 * @var int $pageSize
 */
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BJN Test</title>
  <link href="css/bootstrap.min.css" rel="stylesheet" />
  <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>

<header class="container">
  <div class="row my-4">
    <div class="col-md-8 offset-md-2 d-flex justify-content-between">
      <button
        type="button"
        class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#modal-task-create"
      >Создать задачу</button>

    <?php
    if ($this->isAuthorized()) {
      ?>
      <form action="logout" method="POST">
        <button
          type="submit"
          class="btn btn-danger"
        >Выйти</button>
      </form>
      <?php
    } else {
      ?>
      <button
        type="button"
        class="btn btn-success"
        data-bs-toggle="modal"
        data-bs-target="#modal-auth"
      >Авторизоваться</button>
      <?php
    }
    ?>
    </div>
  </div>
<?php
if (!empty($flashMessage['text'])) {
  ?>
  <div class="row">
    <div class="col-md-8 offset-md-2">
      <div class="alert alert-<?=($flashMessage['type'] ?? 'success')?> alert-dismissible fade show mb-4" role="alert">
        <div class="alert-message"><?=$flashMessage['text']?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    </div>
  </div>
  <?php
}
?>
</header>

<main class="container">
  <div class="row mb-4">
    <div class="col-md-8 offset-md-2 text-center">
      <select class="form-select" id="select-sort">
      <?php
      foreach ($sortingOptions as $k => $v) {
        $selected = false;
        if ($k === $sortRequested) {
          $selected = ' selected="selected"';
        }
        ?>
        <option value="<?=Utils::qs(['sort'=>$k])?>"<?=$selected?>><?=$v?></option>
        <?php
      }
      ?>
      </select>
    </div>
  </div>
<?php
if (empty($items)) {
  ?>
  <div class="row my-4">
    <div class="col-md-8 offset-md-2">
      <div class="p-3 bg-body-tertiary border rounded-3">
        <h5 class="text-center">
        <?php
        if (!empty($total)) {
          ?>
          По указанному фильтру задач нет: <a href="?">сбросить</a>
          <?php
        } else {
          ?>
          Пока что нет ни одной задачи
          <?php
        }
        ?>
        </h5>
      </div>
    </div>
  </div>
  <?php
}
foreach ($items as $item) {
  ?>
  <div class="row my-4" id="task-<?=$item['id']?>">
    <div class="col-md-8 offset-md-2">
      <div class="p-3 bg-body-tertiary border rounded-3">
        <div class="mb-2">
          <span class="badge text-bg-secondary me-1"><?=$item['id']?></span>
        <?php
        if (!empty($item['is_done'])) {
          ?>
          <span class="badge text-bg-success me-1">done</span>
          <?php
        } else {
          ?>
          <span class="badge text-bg-warning me-1">new</span>
          <?php
        }
        if (!empty($item['is_updated'])) {
          ?>
          <span class="badge text-bg-secondary me-1">отредактировано админом</span>
          <?php
        }
        ?>
        </div>
        <div class="text-body-secondary mb-2">
          <div><?=Utils::htmlize($item['author'])?> (<?=Utils::htmlize($item['email'])?>)</div>
        </div>
        <div class="mb-2"><?=nl2br(Utils::htmlize($item['content']))?></div>
        <?php
        if ($this->isAuthorized()) {
          ?>
          <div class="mt-3">
            <button
              type="button"
              class="btn btn-secondary btn-sm btn-task-update"
              data-id="<?=$item['id']?>"
              data-action="task"
            >Редактировать</button>
          </div>
          <?php
        }
        ?>
      </div>
    </div>
  </div>
  <?php
}

$this->tpl('_pager', [
  'page'=>$page,
  'pageSize'=>$pageSize,
  'total'=>$total,
]);
?>
</main>

<?php
// Дальше только модалки.
// Создание.
?>
<div class="modal" tabindex="-1" id="modal-task-create">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Создание новой задачи</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="task-create" method="POST">
          <div class="mb-3">
            <label class="form-label">Ваше имя</label>
            <input type="text" name="author" class="form-control" value="User Name"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Ваш Email</label>
            <input type="email" name="email" class="form-control" value="mail@example.com"/>
            <div class="form-text">Мы его всем покажем.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Текст задачи</label>
            <textarea name="content" class="form-control" rows="5">Какой-нибудь текст задачи...</textarea>
          </div>
          <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary">Создать</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
// Редактирование
?>
<div class="modal" tabindex="-1" id="modal-task-update">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Редактирование задачи <span id="modal-task-update-task-id"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="task-update" method="POST">
          <input type="hidden" name="id" value="" />
          <div class="mb-3">
            <input type="text" name="author" class="form-control" value="" disabled="disabled" />
          </div>
          <div class="mb-3">
            <input type="text" name="email" class="form-control" value="" disabled="disabled" />
          </div>
          <div class="form-check mb-3">
            <input type="hidden" name="is_done" value="0" />
            <input class="form-check-input" type="checkbox" name="is_done" value="1" />
            <label class="form-check-label" for="task-done">
              Задача выполнена
            </label>
          </div>
          <div class="mb-3">
            <label class="form-label">Текст задачи</label>
            <textarea name="content" class="form-control" rows="5"></textarea>
          </div>
          <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary">Сохранить</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
// Авторизация
?>
<div class="modal" tabindex="-1" id="modal-auth">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Авторизация</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="auth" method="POST">
          <div class="mb-3">
            <label class="form-label">Логин</label>
            <input type="text" name="login" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" autocomplete="off" />
          </div>
          <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary">Войти</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
// Ошибка
?>
<div class="modal" tabindex="-1" id="modal-alert">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-body">
          <div class="mb-3 text-center" id="modal-alert-msg"></div>
          <div class="mt-4 text-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ок</button>
          </div>
      </div>
    </div>
  </div>
</div>

<?php
// Шаблон alert'а при ошибках сабмита
?>
<template id="template-alert">
<div class="alert alert-warning alert-dismissible fade show" role="alert">
  <div class="alert-text"></div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
</template>

<script>
(function () {

// Сабмиты форм
document.addEventListener('submit', function (e) {
  e.preventDefault();

  const btn = e.submitter;
  const form = e.target;

  // Убрать из формы возможные алерты
  form.querySelectorAll('.alert').forEach(e => e.remove());
  btn.disabled = true;
  btn.classList.add('disabled');
  btn.blur();

  // Фоновый запрос
  fetch(
    form.action,
    {
      credentials: 'same-origin',
      mode: 'same-origin',
      method: 'post',
      headers: {
        'X-Is-Ajax': 1,
      },
      body: new FormData(form),
    }
  )
  .then(res => res.json())
  .then(data => {
    if (data.reload) {
      window.location.reload();
      return true;
    }
    if (data.message) {
      showAlert(data.message, btn);
    }
  })
  .catch(err => {
    showAlert(err.toString(), btn);
  })
  .finally(() => {
    btn.classList.remove('disabled');
    btn.disabled = false;
  });
});

// Редактирование задачи
document
  .querySelectorAll('.btn-task-update')
  .forEach(x => x.addEventListener('click', function (e) {
    e.preventDefault();

    const btn = this;
    const id = btn.dataset?.id;
    const action = btn.dataset?.action;
    if (!id || !action) {
      return;
    }

    btn.blur();
    btn.disabled = true;
    btn.classList.add('disabled');

    const data = new FormData();
    data.append('id', id);

    // Получить актуальные данные
    fetch(
      action,
      {
        credentials: 'same-origin',
        mode: 'same-origin',
        method: 'post',
        headers: {
          'X-Is-Ajax': 1,
        },
        body: data,
      }
    )
    .then(res => res.json())
    .then(data => {
      if (data.message) {
        showAlertModal(data.message);
        return;
      }
      if (!data.item) {
        return;
      }
      const modal = document.querySelector('#modal-task-update');
      // Заполнение формы
      mapObjectToForm(data.item, modal);
      const header = modal.querySelector('#modal-task-update-task-id');
      if (header) {
        header.textContent = '#' + data.item.id;
      }
      const m = new bootstrap.Modal('#modal-task-update');
      m.show();
    })
    .catch(err => {
      showAlertModal(err.toString());
    })
    .finally(() => {
      btn.classList.remove('disabled');
      btn.disabled = false;
    });
  }));

// Сортировка
document
  .querySelector('#select-sort')
  .addEventListener('change', function (e) {
    window.location.href = this.value;
  });

/**
 * Покажет alert над указанным элементом
 */
function showAlert(msg, node) {
  const tpl = document.querySelector('#template-alert');
  if (!tpl) {
    console.log(msg);
    return false;
  }
  const block = tpl.content.cloneNode(true);
  const textBlock = block.querySelector('.alert-text');
  if (!textBlock) {
    console.log(msg);
    return false;
  }
  textBlock.textContent = msg;
  node.parentNode.insertBefore(block, node);
}

/**
 * Покажет alert в модалке
 */
function showAlertModal(msg) {
  const modal = document.querySelector('#modal-alert');
  if (!modal) {
    console.log(msg);
    return false;
  }

  const textBlock = modal.querySelector('#modal-alert-msg');
  if (!textBlock) {
    console.log(msg);
    return false;
  }
  textBlock.textContent = msg;

  const m = new bootstrap.Modal('#modal-alert');
  m.show();
}

/**
 * Заполнит, по-возможности, из объекта форму внутри контейнера
 */
function mapObjectToForm(obj, container) {
  Object.entries(obj).forEach(([k, v]) => {
    const inputs = container.querySelectorAll(`[name="${k}"]`);
    if (inputs.length > 1) {
      // чекбокс
      input = Array.from(inputs).pop();
      input.checked = Boolean(v);
    } else if (inputs.length === 1) {
      // остальные инпуты
      input = inputs[0];
      input.value = v;
    }
  });
}

})();
</script>

</body>
</html>
