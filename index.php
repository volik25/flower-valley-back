<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization");

require_once 'vendor/autoload.php';
require_once './utils/database.php';
require_once './utils/token.php';
require_once './models/user.php';
require_once './models/product.php';
require_once './models/category.php';
require_once './models/box.php';
require_once './models/static.php';
require_once './models/sale.php';

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as ResponseClass;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

$dataBase = new DataBase();
$user = new User($dataBase);
$sale = new Sale($dataBase);
$product = new Product($dataBase);
$category = new Category($dataBase);
$static = new StaticModel($dataBase);
$box = new Box($dataBase);
$token = new Token();
$app = AppFactory::create();
$app->setBasePath(rtrim($_SERVER['PHP_SELF'], '/index.php'));

// Add error middleware
$app->addErrorMiddleware(true, true, true);
// Add routess
$app->post('/login', function (Request $request, Response $response) use ($user) {
    $requestData = $request->getParsedBody();
    try {
        $response->getBody()->write(json_encode($user->login($requestData['password'])));
        return $response;
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("message" => "Пользователь не найден")));
        return $response->withStatus(401);
    }
});

$app->post('/refresh-token', function (Request $request, Response $response) use ($dataBase) {
    try {
        $user = new User($dataBase);
        $response->getBody()->write(json_encode($user->refreshToken($request->getParsedBody()['token'])));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(401);
    }
});

$app->post('/delete-token', function (Request $request, Response $response) use ($dataBase) {
    try {
        $user = new User($dataBase);
        $response->getBody()->write(json_encode($user->removeRefreshToken($request->getParsedBody()['token'])));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(401);
    }
});

$app->post('/product/send-order', function (Request $request, Response $response) use ($product) {
    try {
        $response->getBody()->write(json_encode($product->send($request->getParsedBody())));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});


$app->get('/product/list', function (Request $request, Response $response) use ($product) {
    try {
        $query = $request->getQueryParams();
        $response->getBody()->write(json_encode($product->search(isset($query['searchString']) ? $query['searchString'] : '')));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->get('/product/{id}', function (Request $request, Response $response) use ($product) {
    try {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $response->getBody()->write(json_encode($product->read($route->getArgument('id'))));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->get('/category/list', function (Request $request, Response $response) use ($category) {
    try {
        $response->getBody()->write(json_encode($category->getList()));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->get('/box/list', function (Request $request, Response $response) use ($box) {
    try {
        $response->getBody()->write(json_encode($box->getList()));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->get('/main-info', function (Request $request, Response $response) use ($static) {
    try {
        $response->getBody()->write(json_encode($static->read()));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});
$app->get('/sale/{id}', function (Request $request, Response $response) use ($sale) {
    try {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $response->getBody()->write(json_encode($sale->getSaleById($route->getArgument('id'))));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});
$app->get('/video/{id}', function (Request $request, Response $response) use ($static) {
    try {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $response->getBody()->write(json_encode($static->readVideoById($route->getArgument('id'))));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->get('/contact-photos', function (Request $request, Response $response) use ($static) {
    try {
        $response->getBody()->write(json_encode($static->readContactPhotos()));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->get('/category/{id}', function (Request $request, Response $response) use ($category) {
    try {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $response->getBody()->write(json_encode($category->read($route->getArgument('id'))));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->group('/', function (RouteCollectorProxy $group) use ($product, $category, $box, $static, $sale, $step) {

    $group->group('product', function (RouteCollectorProxy $productGroup) use ($product) {
        $productGroup->post('', function (Request $request, Response $response) use ($product) {
            try {
                $response->getBody()->write(json_encode($product->create($request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка создания продукта")));
                return $response->withStatus(401);
            }
        });

        $productGroup->post('/order', function (Request $request, Response $response) use ($product) {
            try {
                $response->getBody()->write(json_encode($product->sortProducts($request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка сортировки продуктов")));
                return $response->withStatus(401);
            }
        });

        $productGroup->post('/order-popular', function (Request $request, Response $response) use ($product) {
            try {
                $response->getBody()->write(json_encode($product->sortPopularProducts($request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка сортировки продуктов")));
                return $response->withStatus(401);
            }
        });

        $productGroup->post('/{id}', function (Request $request, Response $response) use ($product) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($product->update($route->getArgument('id'), $request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования продукта")));
                return $response->withStatus(500);
            }
        });

        $productGroup->delete('/{id}', function (Request $request, Response $response) use ($product) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($product->delete($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка удаления продукта")));
                return $response->withStatus(401);
            }
        });
    });

    $group->group('box', function (RouteCollectorProxy $boxGroup) use ($box) {
        $boxGroup->post('', function (Request $request, Response $response) use ($box) {
            try {
                $response->getBody()->write(json_encode($box->create($request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка создания коробки")));
                return $response->withStatus(401);
            }
        });

        $boxGroup->post('/{id}', function (Request $request, Response $response) use ($box) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($box->update($route->getArgument('id'), $request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования коробки")));
                return $response->withStatus(500);
            }
        });

        $boxGroup->delete('/{id}', function (Request $request, Response $response) use ($box) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($box->delete($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка удаления коробки")));
                return $response->withStatus(401);
            }
        });
    });

    $group->group('main', function (RouteCollectorProxy $categoryGroup) use ($static) {
        $categoryGroup->post('', function (Request $request, Response $response) use ($static) {
            try {
                $response->getBody()->write(json_encode($static->updateStatic(1, $request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка измнения банера")));
                return $response->withStatus(401);
            }
        });
    });

    $group->group('sale', function (RouteCollectorProxy $saleGroup) use ($static, $sale) {
        $saleGroup->post('/config', function (Request $request, Response $response) use ($static) {
            try {
                $response->getBody()->write(json_encode($static->updateStatic(4, $request->getParsedBody(), false)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка измнения конфигурации акций")));
                return $response->withStatus(401);
            }
        });
        $saleGroup->post('', function (Request $request, Response $response) use ($sale) {
            try {
                $response->getBody()->write(json_encode($sale->createSale($request->getParsedBody(), isset($_FILES['img']) ? $_FILES['img'] : false)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка добавления акции")));
                return $response->withStatus(500);
            }
        });

        $saleGroup->post('/{id}', function (Request $request, Response $response) use ($sale) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($sale->updateSale($route->getArgument('id'), $request->getParsedBody(), isset($_FILES['img']) ? $_FILES['img'] : false)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования акции")));
                return $response->withStatus(500);
            }
        });

        $saleGroup->delete('/{id}', function (Request $request, Response $response) use ($sale) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($sale->deleteSale($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка удаления акции")));
                return $response->withStatus(500);
            }
        });
    });

    $group->group('comment', function (RouteCollectorProxy $categoryGroup) use ($static) {
        $categoryGroup->post('', function (Request $request, Response $response) use ($static) {
            try {
                $response->getBody()->write(json_encode($static->updateStatic(2, $request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования отзывов")));
                return $response->withStatus(401);
            }
        });
    });

    $group->group('client', function (RouteCollectorProxy $categoryGroup) use ($static) {
        $categoryGroup->post('', function (Request $request, Response $response) use ($static) {
            try {
                $response->getBody()->write(json_encode($static->updateStatic(3, $request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования клиентов")));
                return $response->withStatus(401);
            }
        });
    });

    $group->group('contact-photo', function (RouteCollectorProxy $categoryGroup) use ($static) {
        $categoryGroup->post('', function (Request $request, Response $response) use ($static) {
            try {
                $response->getBody()->write(json_encode($static->createContactPhoto($request->getParsedBody(), $_FILES['img'])));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка добавления фотографии")));
                return $response->withStatus(500);
            }
        });

        $categoryGroup->post('/{id}', function (Request $request, Response $response) use ($static) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($static->updateContactPhoto($route->getArgument('id'), $request->getParsedBody(), $_FILES['img'])));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования фотографии")));
                return $response->withStatus(500);
            }
        });

        $categoryGroup->delete('/{id}', function (Request $request, Response $response) use ($static) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($static->deleteContactPhoto($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка удаления фотографии")));
                return $response->withStatus(500);
            }
        });
    });

    $group->group('video', function (RouteCollectorProxy $categoryGroup) use ($static) {
        $categoryGroup->post('', function (Request $request, Response $response) use ($static) {
            try {
                $response->getBody()->write(json_encode($static->createVideo($request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка создания видео")));
                return $response->withStatus(401);
            }
        });

        $categoryGroup->post('/{id}', function (Request $request, Response $response) use ($static) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($static->updateVideo($route->getArgument('id'), $request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования видео")));
                return $response->withStatus(401);
            }
        });

        $categoryGroup->delete('/{id}', function (Request $request, Response $response) use ($static) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($static->deleteVideo($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка удаления видео")));
                return $response->withStatus(401);
            }
        });
    });

    $group->group('category', function (RouteCollectorProxy $categoryGroup) use ($category) {
        $categoryGroup->post('', function (Request $request, Response $response) use ($category) {
            try {
                $response->getBody()->write(json_encode($category->create($request->getParsedBody(), $_FILES['img'])));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка создания категории")));
                return $response->withStatus(401);
            }
        });

        $categoryGroup->post('/order', function (Request $request, Response $response) use ($category) {
            try {
                $response->getBody()->write(json_encode($category->sortCategories($request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка сортировки категории")));
                return $response->withStatus(401);
            }
        });


        $categoryGroup->post('/{id}/steps', function (Request $request, Response $response) use ($category) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($category->setSteps($route->getArgument('id'), $request->getParsedBody()['steps'])));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования шагов")));
                return $response->withStatus(401);
            }
        });

        $categoryGroup->post('/{id}', function (Request $request, Response $response) use ($category) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($category->update($route->getArgument('id'), $request->getParsedBody(), $_FILES['img'])));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования категории")));
                return $response->withStatus(401);
            }
        });

        $categoryGroup->delete('/{id}', function (Request $request, Response $response) use ($category) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($category->delete($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Невозможно удалить базовую категорию")));
                return $response->withStatus(401);
            }
        });
    });
})->add(function (Request $request, RequestHandler $handler) use ($token, $user) {
    try {
        $jwt = explode(' ', $request->getHeader('Authorization')[0])[1];
        $userId = $token->decode($jwt)->data->id;
        $request = $request->withAttribute('userId', $userId);
        if ($user->checkAdmin($userId)) {
            return $handler->handle($request);
        }

        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => "Отказано в доступе к функционалу администратора")));
        return $response->withStatus(403);
    } catch (Exception $e) {
        $response = new ResponseClass();
        echo json_encode($e);
        $response->getBody()->write(json_encode($e));
        if ($e->getCode() && $e->getCode() != 0) {
            return $response->withStatus($e->getCode());
        }
        return $response->withStatus(500);
    }
});

$app->run();
