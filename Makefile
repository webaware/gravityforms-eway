PKG_NAME			:= gravityforms-eway
PKG_VERSION			:= $(shell sed -rn 's/^Version: (.*)/\1/p' $(PKG_NAME).php)

ZIP					:= .dist/$(PKG_NAME)-$(PKG_VERSION).zip
FIND_PHP			:= find . -path ./vendor -prune -o -path ./node_modules -prune -o -path './.*' -o -name '*.php'
LINT_PHP			:= $(FIND_PHP) -exec php -l '{}' \; >/dev/null
SNIFF_PHP			:= vendor/bin/phpcs -ps
SNIFF_PHP_5			:= $(SNIFF_PHP) --standard=phpcs-5.2.xml
SRC_PHP				:= $(shell $(FIND_PHP) -print)

.PHONY: all lint lint-js lint-php lint-css zip wpsvn js

all:
	@echo please see Makefile for available builds / commands

clean:
	rm -f static/css/* static/js/* .make-flag*

# release product

zip: $(ZIP)

$(ZIP): $(SRC_PHP) .make-flag-js .make-flag-css *.md *.txt
	rm -rf .dist
	mkdir .dist
	git archive HEAD --prefix=$(PKG_NAME)/ --format=zip -9 -o $(ZIP)

# WordPress plugin directory

wpsvn: lint
	svn up .wordpress.org
	rm -rf .wordpress.org/trunk
	mkdir .wordpress.org/trunk
	git archive HEAD --format=tar | tar x --directory=.wordpress.org/trunk

# build JavaScript targets

JS_SRC_DIR		:= source/js
JS_TGT_DIR		:= static/js
JS_SRCS			:= $(shell find $(JS_SRC_DIR) -name '*.js' -print)
JS_TGTS			:= $(subst $(JS_SRC_DIR),$(JS_TGT_DIR),$(JS_SRCS))

js: .make-flag-js

.make-flag-js: $(JS_TGTS)
	@touch .make-flag-js

$(JS_TGTS): $(JS_TGT_DIR)/%.js: $(JS_SRC_DIR)/%.js
	npx babel --source-type script --presets @babel/preset-env --out-file $@ $<
	npx uglify-js $@ --output $(basename $@).min.js -b beautify=false,ascii_only -c -m --comments '/^!/'

# build CSS targets

CSS_SRC_DIR		:= source/scss
CSS_TGT_DIR		:= static/css
CSS_SRCS		:= $(shell find source/scss -maxdepth 1 -name '[a-z]*.scss' -print)
CSS_TGTS		:= $(CSS_SRCS:$(CSS_SRC_DIR)/%.scss=$(CSS_TGT_DIR)/%.min.css)
CSS_TGTS_DEV	:= $(CSS_TGTS:%.min.css=%.dev.css)
CSS_LINT		:= npx stylelint --config .stylelintrc.yml "$(CSS_SRC_DIR)/**/*.scss"

css: .make-flag-css

.make-flag-css: $(CSS_SRCS)
	$(CSS_LINT)
	sass $(foreach source,$(CSS_SRCS),$(source):$(source:$(CSS_SRC_DIR)/%.scss=$(CSS_TGT_DIR)/%.dev.css)) --style=expanded --no-charset
	npx postcss $(CSS_TGTS_DEV) --use autoprefixer --replace --map
	$(foreach dev,$(CSS_TGTS_DEV),npx cleancss -O2 --format beautify --with-rebase --input-source-map $(dev:%=%.map) --output $(dev) $(dev);)
	$(foreach min,$(CSS_TGTS),npx cleancss -O2 --output $(min) $(min:%.min.css=%.dev.css);)
	@touch .make-flag-css

# code linters

lint: lint-js lint-php lint-css

lint-js:
	@echo JavaScript lint...
	@npx eslint --ext js $(JS_SRC_DIR)

lint-php:
	@echo PHP lint...
	@$(LINT_PHP)
	@$(SNIFF_PHP)
	@$(SNIFF_PHP_5)

lint-css:
	@echo CSS lint...
	@$(CSS_LINT)

