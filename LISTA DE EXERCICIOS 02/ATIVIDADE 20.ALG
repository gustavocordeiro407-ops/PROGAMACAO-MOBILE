algoritmo "ATIVIDADE 20"
var
   saque, resto, notas: inteiro
inicio
   repita
      escreva("Valor do saque (positivo): ")
      leia(saque)
   ate saque > 0

   resto <- saque

   // Notas de 100
   notas <- resto \ 100
   escreval("Notas de 100: ", notas)
   resto <- resto % 100

   // Notas de 50
   notas <- resto \ 50
   escreval("Notas de 50: ", notas)
   resto <- resto % 50

   // Notas de 20
   notas <- resto \ 20
   escreval("Notas de 20: ", notas)
   resto <- resto % 20

   // Notas de 10
   notas <- resto \ 10
   escreval("Notas de 10: ", notas)
   resto <- resto % 10

   // Notas de 5
   notas <- resto \ 5
   escreval("Notas de 5: ", notas)
   resto <- resto % 5

   // Notas de 2
   notas <- resto \ 2
   escreval("Notas de 2: ", notas)
   resto <- resto % 2

   // Moedas de 1
   escreval("Moedas de 1: ", resto)
fimalgoritmo