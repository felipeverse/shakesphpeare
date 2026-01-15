.PHONY: build composer-install

composer-install:
	@docker compose -f ./core/docker-compose.local.yml run --rm \
		builder /usr/bin/composer install --no-dev --prefer-dist --no-interaction

build: composer-install
	@echo "Building... - press ctrl+c to stop\n"
	@docker compose -f ./core/docker-compose.local.yml run --rm builder build.php || \
		{ code=$$?; [ $$code -eq 130 ] && echo "Interrupted by user." && exit 0 || exit $$code; }