set :application, 'meumobi'

set :scm, :git
set :deploy_via, :remote_cache
set :git_enable_submodules, true

set :use_sudo, false
ssh_options[:forward_agent] = true

set :normalize_asset_timestamps, false
set :shared_children, %w(public/uploads meu-site-builder/log)

set :php_env, 'production'

namespace :deploy do
  task :permissions do
    run "chmod -R 777 #{release_path}/meu-site-builder/tmp"
  end

  task :shared do
    shared_children.map { |d|
      run "rm -rf #{release_path}/#{d} && ln -fs #{shared_path}/#{d} #{release_path}/#{d}"
    }
  end

  task :environment do
    run "chmod -R 777 #{shared_path}/meu-site-builder/log"
    put php_env, "#{shared_path}/environment"
  end

  task :symlinks do
    run "ln -s #{shared_path}/environment #{release_path}/config/ENVIRONMENT"
    run "cp #{release_path}/config/connections.sample.php #{release_path}/config/connections.php"
  end

  task :platform_check do
    run "php #{release_path}/meu-site-builder/script/check_platform.php"
  end

  namespace :db do
    task :migrate do
      run "php #{release_path}/meu-site-builder/script/migrate.php"
    end
  end
end

after 'deploy:setup', 'deploy:environment'
after 'deploy:update_code', 'deploy:shared'
after 'deploy:update_code', 'deploy:symlinks'
after 'deploy:update_code', 'deploy:permissions'
after 'deploy:update_code', 'deploy:db:migrate'
after 'deploy:update_code', 'deploy:platform_check'
