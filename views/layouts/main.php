<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>

<!DOCTYPE html>
<html lang="ru">

<head>

  <meta charset="utf-8">

  <title>Уроки олимпиадной математики</title>
  <meta name="description" content="">

  <meta name="robots" content="noindex, nofollow" />

  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

  <!-- Template Basic Images Start -->
  <meta property="og:image" content="path/to/image.jpg">
  <link rel="icon" href="img/favicon/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="img/favicon/apple-touch-icon-180x180.png">
  <!-- Template Basic Images End -->

  <!-- grid -->
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.1/css/bootstrap-grid.min.css" />

  <!-- swiper css -->
  <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />

  <!-- fancybox -->
  <link rel="stylesheet" type="text/css" href="css/jquery.fancybox.min.css">

  <link rel="stylesheet" href="css/style.css">

</head>

<body class="body page-index">

  <main>
    <section class="hero">

      <div class="menu-burger"><span class="badge badge-alert">1</span>
        <div class="burger-icon">
          <div class="arrow">
            <svg width="15" height="22" viewBox="0 0 15 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <line y1="-1" x2="16" y2="-1" transform="matrix(-0.792526 -0.609838 0.652403 -0.757873 14.6807 19.5762)"
                stroke="#2A6DD0" stroke-width="2" />
              <line y1="-1" x2="16" y2="-1" transform="matrix(0.792526 -0.609838 -0.652403 -0.757873 1 9.75781)"
                stroke="#2A6DD0" stroke-width="2" />
            </svg>
          </div>
          <div class="burger">
            <svg width="33" height="27" viewBox="0 0 33 27" fill="none" xmlns="http://www.w3.org/2000/svg">
              <line y1="3" x2="33" y2="3" stroke="#E8F5FF" stroke-width="6" />
              <line y1="13" x2="33" y2="13" stroke="#E8F5FF" stroke-width="6" />
              <line y1="24" x2="33" y2="24" stroke="#E8F5FF" stroke-width="6" />
            </svg>
          </div>
        </div>
      </div>
      
      <header class="header">
        <div class="container">
          <div class="header-wrapper">
            <a href="/" class="logo">
              <img src="/img/logo.png" alt="Логотип">
              <span class="name">Название</span>
            </a>
            <!-- /.logo -->

            

            <div class="notification">
              <div class="notific">
                <span class="badge badge-alert">1</span>
                <img src="/img/notification 3.png" alt="">
                Уведомления
              </div>
            </div>

            <nav class="navigation">
              <ul class="menu-nav">
                <li class="menu-item"><a href="#about" class="menu-link active">О нас</a></li>
                <!-- <li class="menu-item"><a href="#contacts" class="menu-link">Контакты</a></li> -->
                <li class="menu-item"><a href="#course" class="menu-link">Курсы</a></li>
                <!-- <li class="menu-item"><a href="#testing" class="menu-link">Тестирование</a></li> 
                -->
                <? if (Yii::$app->user->id) { ?>
                <li class="menu-item"><a href="/account" class="menu-link">Личный кабинет</a></li>
                <? } ?>
              </ul>
            </nav>
            <!-- /.navigation -->
            <? if (!Yii::$app->user->id) { ?>
            <div class="userbar">
              <div class="registration">
                <a href="/registration" class="btn btn-light">Регистрация</a>
              </div>
              <!-- /.registration -->

              <div class="getin">
                <a data-fancybox href="#modal-get-in" class="btn btn-light">Вход</a>
              </div>
              <!-- /.getin -->

              <!-- Модальное окно -->
              <div id="modal-get-in" class="modal modal-get-in" style="display: none;">
                <div class="modal-window">
                  <div class="modal-head">
                    <div class="modal-title">Вход</div>
                    <img src="/img/fogg-clip 1.png" alt="">
                  </div>

                  <div class="modal-body">
                    <form action="/login" method="post">
                      <div class="form-group">
                        <label for="">Ваш логин</label>
                        <input class="form-control" type="text" name="login" placeholder="Логин">
                      </div>
                      <div class="form-group">
                        <label for="">Ваш пароль</label>
                        <input class="form-control" type="password" name="password" placeholder="Пароль">
                      </div>

                      <input type="button" class="btn-submit disabled" value="Войти" />
                    </form>
                  </div>

                  <div class="modal-footer">
                    <a href="/password-restore">Забыли пароль?</a>
                    <a href="/registration">Еще не зарегестрированны?</a>
                  </div>
                </div>
              </div>

              <div class="alert"></div>
              <!-- /.alert -->

              <div class="sistem-btns">
                <div class="personal-area"></div>
                <!-- /.personal-area -->
              </div>
              <!-- /.sistem-btns -->
            </div>
            <!-- /.userbar -->
            <? } ?>
          </div>
        </div>
      </header>

      <div class="container">
<!--         <div class="translate-btn">
          <select>
            <option selected>Рус</option>
            <option>Eng</option>
          </select>
        </div> -->

        <div class="hero-main">
          <div class="decor-2"><img src="/img/decor/decor-2.png" alt=""></div>
          <div class="hero-text">
            <div class="hero-title">ОНЛАЙН – УРОКИ ОЛИМПИАДНОЙ МАТЕМАТИКИ</div>
            <div class="hero-desc">ДЛЯ УЧЕНИКОВ 5 – 11 ЛЕТ</div>
            <div class="hero-btns">
              <a href="#course" class="btn-red">Записаться</a>
              <a href="#course" class="btn-red btn-outline">Курсы</a>
              <div class="decor-1"><img src="/img/decor/decor-1.png" alt=""></div>
            </div>
          </div>
          <!-- /.hero-text -->

          <div class="hero-banner">
            <div class="hero-banner__circle"><img src="/img/hero-img.png" alt=""></div>
          </div>
          <!-- /.hero-banner -->
        </div>

        <div class="arrow-wrap">
          <a href="#about" class="arrow">
            <img src="/img/arrow-down.png" alt="">
          </a>
        </div>
        <!-- /.arrows -->
      </div>
    </section>
    <!-- /.hero -->

    <section id="about" class="about">
      <div class="container">
        <div class="block-title">О нас</div>
        <div class="about-text">
          <p>Мы-онлайн курсы олимпиадой математики. Наша задача пробудить интерес к учебе и научить любить учебу с
            самого начала! Мы поможем выучить математику используя нашу платформу. </p>
        </div>

        <div class="whyus">
          <div class="row">
            <div class="col-lg-6 d-lg-block d-none">
              <div class="whyus-image">
                <img src="/img/whyus-img.png" alt="">
                <div class="circle"></div>
              </div>
            </div>
            <div class="col-lg-6 col">
              <div class="block-title">
                Почему мы?
                <div class="decor-3"><img src="/img/decor/decor-3.png" alt=""></div>
              </div>

              <ul class="whyus-list">
                <li>
                  <div class="whyus-list__title">Эффективная интерактивная платформа</div>
                  <p class="whyus-list__desc">Раннее развитие интеллекта и логики<br> ребенка. Быстрый прогресс.</p>
                </li>
                <li>
                  <div class="whyus-list__title">Самая большая коллекция<br> олимпиадных задач</div>
                  <p class="whyus-list__desc">Тысячи задач и подробные разъяснения способов<br> их решения. От самых
                    простых к самым сложным. </p>
                </li>
                <li>
                  <div class="whyus-list__title">Индивидуальная программа</div>
                  <p class="whyus-list__desc">Подбирается для каждого ученика с учётом<br> его текущего уровня с помощью
                    тестирования.</p>
                </li>
                <li>
                  <div class="whyus-list__title">Комплексная подготовка</div>
                  <p class="whyus-list__desc">Фундаментальноеобучениеи подготовка к математическим<br> олимпиадам от
                    простого к сложному, от начинающего до продвинутого уровней.</p>
                </li>
              </ul>

            </div>
          </div>
        </div>
      </div>
      <div class="bg-decor">
        <div class="el-1">
          <div class="rectangle-13"></div>
          <div class="ellipse-10"></div>
        </div>
        <div class="el-2">
          <div class="ellipse-9"></div>
          <div class="rectangle-14"></div>
        </div>
        <div class="el-3">
          <div class="ellipse-11"></div>
          <div class="ellipse-12"></div>
          <div class="rectangle-15"></div>
        </div>
      </div>
    </section>
    <!-- /.about -->

    <section id="course" class="course">
      <div class="container">
        <div class="block-title">Наши курсы</div>

        <div class="course-price">

          <div class="course-price__slider swiper">
            <div class="swiper-wrapper">

              <div class="swiper-slide">
                <div class="slide-title">Курс НАЧАЛЬНЫЙ</div>
                <ul class="slide-list">
                  <li class="list-item">От 5 до 7 лет</li>
                  <li class="list-item">Без навыков решения задач</li>
                  <li class="list-item">Развитие азов логики и мышления</li>
                </ul>
                <div class="slide-text">
                  <p>Важнейшей задачей курсаявляется развитие самостоятельной логики мышления, которая позволила бы
                    детям строить <br>
                    <a href="/course-beginner.html" class="text-link">Читать дальше...</a>
                  </p>
                </div>

                <div class="slide-price">
                  <div class="slide-price__title">Цена</div>
                  <div class="slide-price__count">1000р/мес.</div>
                  <a href="/course-beginner.html" class="slide-btn">Подробнее</a>
                </div>

              </div>
              <div class="swiper-slide">
                <div class="slide-title">Курс БАЗОВЫЙ</div>
                <ul class="slide-list">
                  <li class="list-item">От 6 до 8 лет </li>
                  <li class="list-item">С небольшими знаниями решения задач </li>
                  <li class="list-item">Навыков решения олимпиадных задач</li>
                </ul>
                <div class="slide-text">
                  <p>Задачей курса является формирование устойчивого интереса к изучению математики,расширение <br>
                    <a href="/course-base.html" class="text-link">Читать дальше...</a>
                  </p>
                </div>

                <div class="slide-price">
                  <div class="slide-price__title">Цена</div>
                  <div class="slide-price__count">1000р/мес.</div>
                  <a href="/course-base.html" class="slide-btn">Подробнее</a>
                </div>

              </div>
              <div class="swiper-slide">
                <div class="slide-title">Курс СРЕДНИЙ</div>
                <ul class="slide-list">
                  <li class="list-item">От 6 до 8 лет </li>
                  <li class="list-item">С небольшими знаниями решения задач </li>
                  <li class="list-item">Навыков решения олимпиадных задач</li>
                </ul>
                <div class="slide-text">
                  <p>Задачей курса является формирование устойчивого интереса к изучению математики,расширение <br>
                    <a href="/course-midle.html" class="text-link">Читать дальше...</a>
                  </p>
                </div>

                <div class="slide-price">
                  <div class="slide-price__title">Цена</div>
                  <div class="slide-price__count">1000р/мес.</div>
                  <a href="/course-midle.html" class="slide-btn">Подробнее</a>
                </div>

              </div>
              <div class="swiper-slide">
                <div class="slide-title">Курс ПРОДВИНУТЫЙ</div>
                <ul class="slide-list">
                  <li class="list-item">От 6 до 8 лет </li>
                  <li class="list-item">С небольшими знаниями решения задач </li>
                  <li class="list-item">Навыков решения олимпиадных задач</li>
                </ul>
                <div class="slide-text">
                  <p>Задачей курса является формирование устойчивого интереса к изучению математики,расширение <br>
                    <a href="/course-advanced.html" class="text-link">Читать дальше...</a>
                  </p>
                </div>

                <div class="slide-price">
                  <div class="slide-price__title">Цена</div>
                  <div class="slide-price__count">1000р/мес.</div>
                  <a href="/course-advanced.html" class="slide-btn">Подробнее</a>
                </div>

              </div>
              <div class="swiper-slide">
                <div class="slide-title">Курс МАГИСТР</div>
                <ul class="slide-list">
                  <li class="list-item">От 9 до 11 лет</li>
                  <li class="list-item">Для детей с склонностью к решению задач</li>
                  <li class="list-item">Навыки самостоятельной работы</li>
                </ul>
                <div class="slide-text">
                  <p>Этот курс рассчитан на учеников, имеющих опыт решения школьных математических задач. <br>
                    <a href="/course-master.html" class="text-link">Читать дальше...</a>
                  </p>
                </div>

                <div class="slide-price">
                  <div class="slide-price__title">Цена</div>
                  <div class="slide-price__count">1000р/мес.</div>
                  <a href="/course-master.html" class="slide-btn">Подробнее</a>
                </div>

              </div>

            </div>
            <div class="swiper-pagination"></div>


          </div>

          <div class="swiper-button-prev"></div>
          <div class="swiper-button-next"></div>

        </div>
      </div>

      <div class="bg-decor">
        <div class="el-4">
          <div class="ellipse-14"></div>
          <div class="ellipse-16"></div>
        </div>
        <div class="decor-book">
          <img src="/img/decor/book.png" alt="">
        </div>
        <div class="el-5">
          <div class="ellipse-15"></div>
        </div>
        <div class="decor-4"><img src="/img/decor/decor-4.png" alt=""></div>
        <div class="decor-5"><img src="/img/decor/decor-5.png" alt=""></div>
      </div>
    </section>
    <!-- /.course -->

    <section class="testing">
      <div class="container">
        <div class="block-title">Тестирование</div>
        <div class="testing-text">
          <p>Определит текущий уровень, предложит соответствующий уровню индивидуальный курс обучения. Пройдите
            тестирование и получите бесплатный пробный двухнедельный курс обучения!</p>
          <p class="testing-move">Переходи!</p>
          <a href="#!" class="testing-btn">Пройти</a>
        </div>
      </div>

      <div class="bg-decor">
        <div class="el-6">
          <div class="ellipse-43"></div>
          <div class="ellipse-45"></div>
        </div>
        <div class="el-7">
          <div class="ellipse-42"></div>
          <div class="ellipse-44"></div>
        </div>
      </div>
    </section>
    <!-- /.testing -->

    <footer class="footer">
      <div class="container">
        <div class="row">
          <div class="col-lg-4 order-lg-1 order-2">
            <div class="logo">
              <img src="/img/logo.png" alt="">
            </div>
            <div class="footer-menu footer-menu__left">
              <a href="#!">Обработка персональных данных</a>
              <a href="#!">Политика конфиденциальности</a>
              <a href="#!">Оплата и гарантии</a>
            </div>
          </div>
          <div class="col-lg-4 order-lg-2 order-1">
            <div class="scrollup">
              <a href="#!" onclick="scrollToTop();return false;">Вернутся наверх</a>
            </div>

            <div class="social">
              <div class="social-title">Подписывайтесь на наши соц.сети</div>
              <div class="social-links">
                <a href="#!" class="link"><img src="/img/socials/youtube.png" alt=""></a>
                <a href="#!" class="link"><img src="/img/socials/fb.png" alt=""></a>
                <a href="#!" class="link"><img src="/img/socials/inst.png" alt=""></a>
                <a href="#!" class="link"><img src="/img/socials/vk.png" alt=""></a>
              </div>
            </div>
          </div>
          <div class="col-lg-4 order-lg-3 order-3">
            <div class="footer-menu footer-menu__right">
              <a href="#!">Помощь</a>
              <a href="#!">Публичная оферта</a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col">
            <div class="copyright">
              Илюстрации: <a href="https://icons8.com/license">https://icons8.com/license</a><br>
              Дизайн: <a href="#!">@web.designerm</a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <div class="messenger-widget">
      <a href="#!" target="_blank">
        <img src="/img/messenger-widget.png" alt="">
      </a>
    </div>
  </main>

  <!-- jquery -->
  <script src="//code.jquery.com/jquery-3.2.1.min.js"></script>

  <!-- fancybox -->
  <script src="js/jquery.fancybox.min.js"></script>

  <!-- swiper js -->
  <script src="//unpkg.com/swiper@7/swiper-bundle.min.js"></script>

  <script src="js/scripts.js"></script>
  <script src="js/swiper.js"></script>

</body>

</html>


<?php $this->endPage() ?>
