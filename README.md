# 📑 Sistema de Controle de Horários de Emissão de Certidões - 3º Registro de Imóveis de São Luís  

Este sistema web foi desenvolvido para registrar e monitorar os horários de entrada e saída dos processos de emissão de certidões no **3º Registro de Imóveis de São Luís**. Ele calcula a média de tempo de emissão considerando **somente as horas úteis** do cartório.  

## 🛠️ Tecnologias Utilizadas  

- **Linguagem Backend:** PHP (versão 5.5.19)  
- **Banco de Dados:** MySQL (phpMyAdmin)  
- **Frontend:** HTML, CSS, JavaScript  
- **Ambiente de Desenvolvimento:** XAMPP (versão 5.5.19)  

## 🚀 Funcionalidades  

✅ Registro dos horários de entrada e saída dos processos de emissão de certidões.  
✅ Cálculo da média de tempo considerando apenas **horas úteis**.  
✅ Interface intuitiva para controle dos tempos de emissão.  
✅ Relatórios e métricas para acompanhamento do desempenho do cartório.  

## 📦 Instalação e Configuração  

1. **Clone o repositório:**  
   ```bash
   git clone https://github.com/1fms/SistemaCertidao.git
   ```

2. **Configure o ambiente:**  
   - Instale o XAMPP 5.5.19
   - Verifique se o PHP 5.5.19 está ativado.  
   - Inicie os serviços do XAMPP.  

3. **Importe o banco de dados:**  
   - Acesse o **phpMyAdmin**.  
   - Crie um banco de dados (exemplo: `certidoes`).  
   - Importe o arquivo `.sql` fornecido no repositório.  

4. **Configure o acesso ao banco de dados no arquivo de conexão (`config.php`):**  
   ```php
   <?php
   $conn = new mysqli("localhost", "usuario", "senha", "certidoes");
   if ($conn->connect_error) {
       die("Falha na conexão: " . $conn->connect_error);
   }
   ?>
   ```

5. **Acesse o sistema no navegador:**  
   ```
   http://localhost/seuprojeto
   ```
