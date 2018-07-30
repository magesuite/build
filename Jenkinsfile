pipeline {
  parameters {
    string(name: 'ARTIFACT_REPO', description: 'Artifact git repo URL')
  }

  stages {
    stage('Clone current artifacts') {
        checkout([$class: 'GitSCM',
              branches: [[name: "*/master"]],
              userRemoteConfigs: [[credentialsId: '1aa37c8c-73f1-4b3c-a2e5-149de20b989c',
              url: params.ARTIFACT_REPO]]])
        
    }
    
    stage('Decrypt composer auth') {
      steps {
        script {
          sh 'ansible-vault --vault-password-file=~/.raccoon-vault-password --output=auth.json decrypt auth.json.encrypted'
        }
      } 
    }
    when { expression { return fileExists('auth.json.encrypted') && !fileExists('auth.json') } }
    
    stage('Install composer deps') {
      steps {
        script {
          sh 'php /usr/local/bin/composer update'
        }
      } 
    }
    when { expression { return !fileExists('vendor') } }
  }
}
