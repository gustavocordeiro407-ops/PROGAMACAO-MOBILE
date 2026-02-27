algoritmo "ATIVIDADE 20"
var p, a, imc: real
inicio
   escreva("Peso (kg): ")
   leia(p)
   escreva("Altura (m): ")
   leia(a)
   imc <- p / (a^2)
   escreval("IMC: ", imc:4:2)

   se imc < 18.5 entao
      escreva("Abaixo do peso")
   senao
      se imc < 25 entao
         escreva("Peso normal")
      senao
         se imc < 30 entao
            escreva("Sobrepeso")
         senao
            escreva("Obesidade")
         fimse
      fimse
   fimse
fimalgoritmo