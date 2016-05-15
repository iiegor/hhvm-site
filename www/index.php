<?hh

require_once('../vendor/autoload.php');
require_once('./utils.php');

$cssMap = (array) json_decode(file_get_contents(__DIR__ . '/dist/css-map.json'));

// NOTE: Is memoize useful here?
<<__Memoize>>
function getClassName($classAttr): string {
	$classes = [];

	$classesArr = explode(' ', $classAttr);
	foreach ($classesArr as $className) {
		$classes[] = @$GLOBALS['cssMap'][$className];
	}

	return implode(' ', $classes);
}

abstract class :fb:base extends :x:element {

	attribute
		Stringish id,
		Stringish class;

	protected final function setClassAttr(): void {
		$this->setAttribute('class', getClassName($this->:class));
	}
}

/**
 * Base elements
 */
class :fb:div extends :fb:base {

	protected function render(): XHPRoot {
		$this->setClassAttr();

		return <div id={$this->:id} class={$this->:class}>{$this->getChildren()}</div>;
	}
}

class :fb:a extends :fb:base {

	attribute
		Stringish href;

	protected function render(): XHPRoot {
		$this->setClassAttr();

		return <a id={$this->:id} class={$this->:class} href={$this->:href}>{$this->getChildren()}</a>;
	}
}

class :fb:ul extends :fb:base {

	protected function render(): XHPRoot {
		$this->setClassAttr();

		return <ul id={$this->:id} class={$this->:class}>{$this->getChildren()}</ul>;
	}
}

class :fb:li extends :fb:base {

	protected function render(): XHPRoot {
		$this->setClassAttr();

		return <li id={$this->:id} class={$this->:class}>{$this->getChildren()}</li>;
	}
}

class :fb:js-scope extends :x:element implements XHPAwaitable {
  use XHPAsync;

  protected async function asyncRender(): Awaitable<XHPRoot> {
    $calls = Vector { };
    $instances = Vector { };
    $this->setContext(':x:js-scope/calls', $calls);
    $this->setContext(':x:js-scope/instances', $instances);

    $child_waithandles = Vector { };
    foreach ($this->getChildren() as $child) {
      if ($child instanceof :x:composable-element) {
        $child->__transferContext($this->getAllContexts());
        $child_waithandles[] = (async () ==> await $child->__flushSubtree())();
      } else {
        invariant_violation(gettype($child).' is not an :x:composable-element');
      }
    }
    $children = await HH\Asio\v($child_waithandles);
    $this->replaceChildren();

    return
      <x:frag>
        {$children}
        <script>
        require('InitialJSLoader').handleServerJS({json_encode($instances)},{json_encode($calls)});
        </script>
      </x:frag>;
  }
}

/**
 * UI elements
 */
class :fb:root extends :x:element {

	protected function render(): XHPRoot {
		return
			<fb:js-scope>
				<fb:div class="public/_li">{$this->getChildren()}</fb:div>
			</fb:js-scope>;
	}
}

class :fb:navbar extends :x:element {

	attribute string brand @required;

	protected function render(): XHPRoot {
		$root = <fb:div class="Navbar/root" />;

		$nav =
			<fb:ul class="Nav/root">
				<fb:li class="Nav/navItem NavItem/active">
					<fb:a href="/" class="NavItem/link">Home</fb:a>
				</fb:li>
				<fb:li class="Nav/navItem">
					<fb:a href="/resources" class="NavItem/link">Resources</fb:a>
				</fb:li>
			</fb:ul>;

		$root->appendChild(<fb:div class="Navbar/navbarContainer public/u-alignCenter public/u-alignItemsMiddle">{$nav}</fb:div>);

		return $root;
	}
}

class :fb:jstest extends :x:element {
	use XHPHelpers;
	use XHPJSCall;
	use XHPJSInstance;

	attribute :xhp:html-element;

	protected function render(): XHPRoot {
		$this->setAttribute('id', 'u_0_2');

		$this->jsCall(
			'MyJSModule',
			'myJSFunction',
			'hello, world.',
			XHPJS::Instance($this)
		);

		$this->constructJSInstance(
			'MyJSController',
			XHPJS::Element($this),
			'herp derp',
		);

		return <div>In :fb:jstest::render()</div>;
	}
}

class :fb:react extends :x:element {
	use XHPHelpers;
	use XHPReact;

	attribute
		:xhp:html-element,
		string some-attribute @required;

	protected function render(): XHPRoot {
		$this->setAttribute('id', 'u_0_1');

		$this->constructReactInstance(
			'MyReactClass',
			Map { 'someAttribute' => $this->:some-attribute }
		);

		return <div />;
	}
}

print(
	<x:doctype>
	<html lang="en">
		<head>
			<meta charset="utf-8" />
			<title>Application</title>
			<link type="text/css" rel="stylesheet" href={$getAssetSrc('style', 'css')} crossorigin="anonymous" />
			<script src="//cdnjs.cloudflare.com/ajax/libs/react/15.0.2/react.js" crossorigin="anonymous"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/react/15.0.2/react-dom.min.js" crossorigin="anonymous"></script>
			<script src={$getAssetSrc('app', 'js')} crossorigin="anonymous"></script>
		</head>
		<body>
			<fb:root>
				<fb:navbar brand="Application" />
				<fb:div class="Notification/root Notification/error">
					<fb:a class="Notification/notificationLink" href="/?err-stack=:fb:render()">Error calling 'await :fb:posts::fetchPosts()'</fb:a>
				</fb:div>
				
				<fb:div class="Container/root public/u-alignCenter">
					<fb:jstest />
					<fb:react some-attribute="some value" />
				</fb:div>
			</fb:root>
		</body>
	</html>
	</x:doctype>
);