load 'deploy' if respond_to?(:namespace)
load 'sitebuilder/Capfile'

set :repository, 'git@repos.ipanemax.com:partners.meumobi.git'
set :user, 'meumobi'

# production settings. do not change unless PROD env moves. if you need to
# deploy to INT or another env, create or modify a task for it.
set :deploy_to, '/home/meumobi/PROJECTS/meumobi.com'
role :app, 'elefante.ipanemax.com'

task :integration do
  # where will INT be now?
  set :php_env, 'integration'
  set :deploy_to, '/home/meumobi/PROJECTS/partners.meumobilesite.com'
  role :app, 'bonita.ipanemax.com'
end
